<?php

namespace aoiawd;

use aoiawd\plugin\PluginManager;
use aoicommon\socket\AsyncTCPServer;
use aoicommon\helper\CommonHelper;

use aoiawd\datastruct\PwnProcess;
use aoiawd\datastruct\PwnSocket;
use aoiawd\datastruct\PwnStreamLog;

class LogReceiver
{
    const WEB = 'web';
    const NEW_PROCESS = "new_process";
    const CURRENT_PROCESS = "pid_list";
    const NEW_FILE = "file";
    const PWN = "pwn";
    const PING = "ping";

    private $server;
    private $logger;
    private $alert = null;
    private $insert = true;
    private $currentType;

    /** @var PwnSocket */
    private $pwnSocket = [];

    /** @var PwnProcess */
    private $pwnProcess = [];

    private $currentProcess = [];

    public function __construct(AsyncTCPServer $server)
    {
        $this->server = $server;
        $this->logger = $this->logger = CommonHelper::getLogger(self::class);
        $this->server->setCallback([$this, 'onReceive']);
    }

    private function processPWNStream($data, $socket)
    {
        $this->currentType = "PWN";
        $resId = (int) $socket;
        $socket_storage = &$this->pwnSocket[$resId];
        $pid = $socket_storage->pid;
        if (isset($this->pwnProcess[$pid])) {
            $data_storage = &$this->pwnProcess[$pid];
            $stream_storage = &$data_storage->streamlog;
            $latest_recoard = null;
            if (end($stream_storage)) {
                $latest_recoard = &$stream_storage[key($stream_storage)];
            } else {
                $latest_recoard = new PwnStreamLog;
            }
            if ($socket_storage->type == $latest_recoard->type) {
                $latest_recoard->buffer .= $data;
            } else {
                switch ($latest_recoard->type) {
                    case "stdin":
                        $data_storage->stdin['group']++;
                        $data_storage->stdin['byte'] += strlen($latest_recoard->buffer);
                        break;
                    case "stdout":
                        $data_storage->stdout['group']++;
                        $data_storage->stdout['byte'] += strlen($latest_recoard->buffer);
                        break;
                }
                $log = new PwnStreamLog();
                $log->type = $socket_storage->type;
                $log->buffer = $data;
                $stream_storage[] = $log;
            }
            if ($data === false) {
                unset($data_storage->socket);
                PluginManager::getInstance()->invoke($this, 'PWN', 'processLog', $data_storage);
                foreach ($data_storage->streamlog as &$log) {
                    $log->buffer = base64_encode($log->buffer);
                }
                $this->insertDB('pwn', $data_storage);
                unset($this->pwnProcess[$pid]);
                AoiAWD::getInstance()->triggerUpdate('pwn');
            }
        }
    }

    private function handleNewPWN($payload, $socket)
    {
        $resId = (int) $socket;
        $socketobj = new PwnSocket();
        $socketobj->pid = $payload->pid;
        $socketobj->type = $payload->type;
        $this->pwnSocket[$resId] = $socketobj;
        if (!isset($this->pwnProcess[$payload->pid])) {
            $process = new PwnProcess();
            $process->time = time();
            $process->bin = $payload->file;
            $process->maps = $payload->maps;
            $this->pwnProcess[$payload->pid] = $process;
        }
        $this->pwnProcess[$payload->pid]->socket[] = $socket;
        $this->server->useStream($resId);
    }

    private function handleWeb($payload, $socket)
    {
        $this->currentType = "Web";
        $data = [
            'time' => time(),
            'script' => $payload->script,
            'method' => $payload->method,
            'uri' => $payload->uri,
            'remote' => $payload->remote,
            'header' => $payload->header,
            'get' => $payload->get,
            'post' => $payload->post,
            'cookie' => $payload->cookie,
            'file' => $payload->file,
            'buffer' => $payload->buffer,
        ];
        $processArray = function ($array) use (&$processArray) {
            $new = [];
            foreach ($array as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $value = $processArray($value);
                } else {
                    $value = urldecode($value);
                }
                $new[$key] = $value;
            }
            return $new;
        };
        $decoded = $processArray($data);
        $filterData = PluginManager::getInstance()->invoke($this, 'Web', 'processLog', $decoded);
        fwrite($socket,  base64_encode($filterData['buffer']) . "\n");
        $this->insertDB("web", $data);
    }

    private function handleNewFile($payload)
    {
        $this->currentType = "FileSystem";
        static $dict = [
            0x00000001 => 'ACCESS',
            0x00000002 => 'MODIFY',
            0x00000004 => 'ATTRIB',
            0x00000008 => 'CLOSE_WRITE',
            0x00000010 => 'CLOSE_NOWRITE',
            0x00000018 => 'CLOSE',
            0x00000020 => 'OPEN',
            0x00000040 => 'MOVED_FROM',
            0x00000080 => 'MOVED_TO',
            0x000000C0 => 'MOVE',
            0x00000100 => 'CREATE',
            0x00000200 => 'DELETE',
            0x00000400 => 'DELETE_SELF',
            0x00000800 => 'MOVE_SELF'
        ];
        $data = [
            "time" => time(),
            "path" => $payload->path,
            "isdir" => (bool) ($payload->event & 0x40000000),
            "oper" => $dict[$payload->event & 0x0FFFFFFF] ?? 'UNKNOWN',
            "size" => $payload->size,
            "content" => $payload->content,
        ];
        PluginManager::getInstance()->invoke($this, 'FileSystem', 'processLog', $data);
        $this->insertDB("filesystem", $data);
    }

    private function handleNewProcess($payload)
    {
        $this->currentType = "Process";
        $data = [
            "time" => time(),
            "pid" => $payload->pid,
            "ppid" => $payload->ppid,
            "uid" => $payload->uid,
            "user" => $payload->username,
            "bin" => $payload->cmd,
            "arg" => rtrim($payload->param)
        ];
        PluginManager::getInstance()->invoke($this, 'Process', 'processLog', $data);
        $this->insertDB("process", $data);
        $data['id'] = $data['pid'];
        $data['time'] = \date('Y-m-d H:i:s', $data['time']);
        $this->currentProcess[$data['pid']] = $data;
    }

    private function insertDB($collaction, $data)
    {
        $insertResult = null;
        if ($this->alert != null) {
            $data['alerted'] = true;
        }
        if ($this->insert) {
            $insertResult = $this->db()->$collaction->insertOne(DBHelper::escape($data));
            DBHelper::addCollactionCount($collaction);
        }
        if ($this->alert != null) {
            $logId = null;
            if ($insertResult != null) {
                $logId = $insertResult->getInsertedId();
            }
            $data = [];
            foreach ($this->alert as $alert) {
                if ($logId != null) {
                    $logId = $logId->jsonSerialize()['$oid'];
                }
                $data[] = [
                    'time' => time(),
                    'type' => $this->currentType,
                    'plugin' => $alert[0],
                    'message' => $alert[1],
                    'reference' => [
                        'page' => ceil(DBHelper::getCollactionCount($collaction) / 20),
                        'id' => $logId
                    ]
                ];
            }
            $this->db()->alert->insertMany($data);
            AoiAWD::getInstance()->increaseAlert();
            DBHelper::addCollactionCount('alert', count($this->alert));
        }
        $this->insert = true;
        $this->alert = null;
    }

    private function handleProcessList($payload)
    {
        $delete = array_diff(array_keys($this->currentProcess), $payload);
        foreach ($delete as $pid) {
            unset($this->currentProcess[$pid]);
        }
    }

    public function onReceive($data, $socket)
    {
        // $this->logger->debug("Receive: {$data}");
        $resId = (int) $socket;
        if (isset($this->pwnSocket[$resId])) {
            $this->processPWNStream($data, $socket);
            return;
        }
        if ($data === false) {
            return;
        }
        $data = rtrim($data);
        $data = json_decode($data);
        if ($data == null) {
            return;
        }
        if (isset($data->type)) {
            switch ($data->type) {
                case self::WEB:
                    $this->handleWeb($data->data, $socket);
                    AoiAWD::getInstance()->triggerUpdate('web');
                    break;
                case self::NEW_FILE:
                    $this->handleNewFile($data->data);
                    AoiAWD::getInstance()->triggerUpdate('file');
                    break;
                case self::NEW_PROCESS:
                    $this->handleNewProcess($data->data);
                    AoiAWD::getInstance()->triggerUpdate('process');
                    break;
                case self::CURRENT_PROCESS:
                    $this->handleProcessList($data->data);
                    AoiAWD::getInstance()->triggerUpdate('process');
                    break;
                case self::PWN:
                    $this->handleNewPWN($data->data, $socket);
                    break;
                case self::PING:
                    fwrite($socket, json_encode(['type' => 'pong', 'data' => []]) . "\n");
                    break;
            }
        }
    }

    private function db()
    {
        return DBHelper::getDB();
    }

    public function start()
    {
        $this->server->start();
    }

    public function setAlert(string $plugin, string $alert)
    {
        $this->alert[] = [$plugin, $alert];
    }

    public function abortInsert()
    {
        $this->insert = false;
    }

    public function getCurrentProcess()
    {
        return $this->currentProcess;
    }
}
