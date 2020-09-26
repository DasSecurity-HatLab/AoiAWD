<?php

namespace aoiawd\api;

use MongoDB\BSON\ObjectID;
use aoicommon\api\BaseAPIController;
use Amp\Http\Status;
use aoiawd\DBHelper;
use aoiawd\AoiAWD;
use aoiawd\plugin\PluginManager;
use Amp\Http\Server\Response;

class v1 extends BaseAPIController
{

    public function actionDownloadPWNAutoScript()
    {
        $buffer = "Not IMPL Yet.";
        $header = [
            'content-type' => 'application/octet-stream'
        ];
        return new Response(Status::OK, $header, $buffer);
    }

    public function actionDownloadPWN()
    {
        $id = $this->_GET->id ?? null;
        $type = $this->_GET->type ?? null;
        if ($id == null) {
            return $this->response(Status::NOT_FOUND);
        }
        $collaction = $this->db()->pwn;
        $data = $collaction->findOne(['_id' => new ObjectID($id)]);
        if ($data == null) {
            return $this->response(Status::NOT_FOUND);
        }
        $buffer = '';
        switch ($type) {
            case 'maps':
                $buffer = base64_decode($data['maps']);
                break;
            case 'stream':
                $part = $this->_GET->part ?? null;
                if ($part !== null) {
                    switch ($part) {
                        case 'all':
                            foreach ($data->streamlog as $log) {
                                $buffer .= base64_decode($log->buffer);
                            }
                            break;
                        default:
                            $logs = $data->streamlog->jsonSerialize();
                            if (isset($logs[$part])) {
                                $buffer = base64_decode($logs[$part]->buffer);;
                            }
                            break;
                    }
                }
                break;
        }
        $header = [
            'content-type' => 'application/octet-stream'
        ];
        return new Response(Status::OK, $header, $buffer);
    }

    public function actionPWNDetail()
    {
        $id = $this->_GET->id ?? null;
        if ($id == null) {
            return $this->response(Status::NOT_FOUND);
        }
        $collaction = $this->db()->pwn;
        $data = $collaction->findOne(['_id' => new ObjectID($id)]);
        if ($data == null) {
            return $this->response(Status::NOT_FOUND);
        }
        return $this->response(Status::OK, $data);
    }

    public function actionGeneratePWNBin()
    {
        $binary = $this->_POST->binary ?? null;
        $host = $this->_POST->host ?? null;
        $port = $this->_POST->port ?? null;
        if ($binary == null || $host == null || $port == null) {
            return $this->response(Status::BAD_REQUEST);
        }
        $guardian_path = PHAR_BASE . '/resource/guardian';
        $guardian_binary = file_get_contents($guardian_path);
        $buffer = $guardian_binary . base64_decode($binary) . pack("L", strlen($guardian_binary)) . pack("N", ip2long($host)) . pack("n", $port);
        $header = [
            'content-type' => 'application/octet-stream'
        ];
        return new Response(Status::OK, $header, $buffer);
    }

    public function actionListPWN()
    {
        $projection = [
            'time' => true,
            'bin' => true,
            'stdin' => true,
            'stdout' => true,
        ];
        return $this->fetchPreview('pwn', $projection);
    }

    public function actionDownloadWebAutoScript()
    {
        $id = $this->_GET->id ?? null;
        if ($id == null) {
            return $this->response(Status::NOT_FOUND);
        }
        $collaction = $this->db()->web;
        $data = $collaction->findOne(['_id' => new ObjectID($id)]);
        if ($data == null) {
            return $this->response(Status::NOT_FOUND);
        }
        $data = $data->jsonSerialize();
        $header_str = "";
        foreach ($data->header as $key => $value) {
            $value = urldecode($value);
            if ($key == "Cookie" || $key == "Host" || $key == "Accept-Encoding") {
                continue;
            }
            $header_str .= '        ' . var_export($key, true) . ' . ": " . ' . var_export($value, true) . ",\n";
        }
        $cookie_str = "";
        foreach ($data->cookie as $key => $value) {
            $value = urldecode($value);
            $cookie_str .= '        ' . var_export($key, true) . ' => ' . var_export($value, true) . ",\n";
        }
        $get_str = "";
        foreach ($data->get as $key => $value) {
            $value = urldecode($value);
            $get_str .= '        ' . var_export($key, true) . ' => ' . var_export($value, true) . ",\n";
        }
        $post_str = "";
        foreach ($data->post as $key => $value) {
            $value = urldecode($value);
            $post_str .= '        ' . var_export($key, true) . ' => ' . var_export($value, true) . ",\n";
        }
        $code = '<?php' . PHP_EOL;
        $code .= '$host = "http://" . ' . urldecode(var_export($data->header->Host, true)) . ';' . PHP_EOL;
        $code .= 'sendPayload($host);' . PHP_EOL;
        $code .= 'exit;' . PHP_EOL;
        $code .= '$hosts = [];' . PHP_EOL;
        $code .= 'for ($i = 1; $i < 255; $i++) {' . PHP_EOL;
        $code .= '    $hosts[] = sprintf("192.168.0.%d", $i);' . PHP_EOL;
        $code .= '}' . PHP_EOL;
        $code .= 'foreach ($hosts as $host) {' . PHP_EOL;
        $code .= '    $host = "http://{$host}";' . PHP_EOL;
        $code .= '    echo "Sending to: {$host}\n";' . PHP_EOL;
        $code .= '    sendPayload($host);' . PHP_EOL;
        $code .= '}' . PHP_EOL;
        $code .= 'function sendPayload($url)' . PHP_EOL;
        $code .= '{' . PHP_EOL;
        $code .= '    $curl = new Curl();' . PHP_EOL;
        $code .= '    $curl->setUrl($url . ' . var_export(explode('?', $data->uri)[0], true) . ');' . PHP_EOL;
        $code .= '    $curl->setHeader([' . PHP_EOL;
        $code .= '    ' . $header_str . '' . PHP_EOL;
        $code .= '    ]);' . PHP_EOL;
        if ($cookie_str != "") {
            $code .= '    $curl->setCookie([' . PHP_EOL;
            $code .= '    ' . $cookie_str . '' . PHP_EOL;
            $code .= '    ]);' . PHP_EOL;
        }
        if ($get_str != "") {
            $code .= '    $curl->setGet([' . PHP_EOL;
            $code .= '    ' . $get_str . '' . PHP_EOL;
            $code .= '    ]);' . PHP_EOL;
        }
        if ($post_str != "") {
            $code .= '    $curl->setPost([' . PHP_EOL;
            $code .= '    ' . $post_str . '' . PHP_EOL;
            $code .= '    ]);' . PHP_EOL;
        }
        $code .= '    echo $curl->exec();' . PHP_EOL;
        $code .= '    echo PHP_EOL;' . PHP_EOL;
        $code .= '}' . PHP_EOL;
        $code .= '?>' . PHP_EOL;
        $code .= PHP_EOL;
        $code .= file_get_contents(PHAR_BASE . '/resource/Curl.php');
        $header = [
            'content-type' => 'application/octet-stream'
        ];
        return new Response(Status::OK, $header, $code);
    }

    public function actionListAlert()
    {
        $projection = [
            'time' => true,
            'type' => true,
            'plugin' => true,
            'message' => true,
            'reference' => true,
        ];
        return $this->fetchPreview('alert', $projection);
    }

    public function actionListPlugin()
    {
        $data = PluginManager::getInstance()->listPlugin();
        $data = ['result' => '1', 'message' => 'ok', 'data' => $data];
        return $this->response(Status::OK, $data);
    }

    public function actionReloadPlugin()
    {
        PluginManager::getInstance()->loadPlugin();
        $data = ['result' => '1', 'message' => 'ok'];
        return $this->response(Status::OK, $data);
    }

    public function actionWebDetail()
    {
        $id = $this->_GET->id ?? null;
        if ($id == null) {
            return $this->response(Status::NOT_FOUND);
        }
        $collaction = $this->db()->web;
        $data = $collaction->findOne(['_id' => new ObjectID($id)]);
        if ($data == null) {
            return $this->response(Status::NOT_FOUND);
        }
        return $this->response(Status::OK, $data);
    }

    public function actionListWeb()
    {
        $projection = [
            'time' => true,
            'method' => true,
            'uri' => true,
            'remote' => true,
        ];
        return $this->fetchPreview('web', $projection);
    }

    public function actionDownloadFile()
    {
        $id = $this->_GET->id ?? null;
        if ($id == null) {
            return $this->response(Status::NOT_FOUND);
        }
        $collaction = $this->db()->filesystem;
        $data = $collaction->findOne(['_id' => new ObjectID($id)]);
        if ($data == null) {
            return $this->response(Status::NOT_FOUND);
        }
        $buffer = base64_decode($data['content']);
        $header = [
            'content-type' => 'application/octet-stream'
        ];
        return new Response(Status::OK, $header, $buffer);
    }

    public function actionListFilesystem()
    {
        $projection = [
            'time' => true,
            'path' => true,
            'oper' => true,
            'isdir' => true,
            'content' => true
        ];
        return $this->fetchPreview('filesystem', $projection);
    }

    public function actionListProcess()
    {
        $projection = [
            "time" => true,
            "pid" => true,
            "ppid" => true,
            "uid" => true,
            "user" => true,
            "bin" => true,
            "arg" => true,
        ];
        return $this->fetchPreview('process', $projection);
    }

    private function fetchPreview($collaction_name, $projection)
    {
        $collaction = $this->db()->$collaction_name;
        $page = intval($this->_GET->page ?? 1);
        $count = intval($this->_GET->count ?? 20);
        $total = max(ceil(DBHelper::getCollactionCount($collaction_name) / $count), 1);
        $offset = intval($page == 0 ? ($total - 1) * $count : ($page - 1) * $count);
        $datas = $collaction->find([], [
            'projection' => $projection,
            'skip' => $offset,
            'limit' => $count,
        ]);
        $rows = [];
        foreach ($datas as $data) {
            $data['id'] = $data['_id']->jsonSerialize()['$oid'];
            $data['time'] = \date('Y-m-d H:i:s', $data['time']);
            if (isset($data['content'])) {
                $data['content'] = base64_encode(substr(base64_decode($data['content']), 0, 50));
            }
            $rows[] = $data;
        }
        $result = [
            'page' => $page,
            'last_page' => $total,
            'data' => $rows
        ];
        return $this->response(Status::OK, $result);
    }
    public function actionInfo()
    {
        $return = [
            "timestamp_lastupdate" => date("Y-m-d H:i:s", AoiAWD::getInstance()->getLastUpdate()),
            "count_alert" => AoiAWD::getInstance()->getAlertCount(),
            "timestamp_runningtime" => gmdate('H:i:s', AoiAWD::getInstance()->getUpTime())
        ];
        return $this->response(Status::OK, $return);
    }

    public function actionListCurrentProcess()
    {
        return $this->response(Status::OK, [
            'page' => 1,
            'last_page' => 1,
            'data' => array_values(AoiAWD::getInstance()->getLogReceiver()->getCurrentProcess())
        ]);
    }

    public function actionCurrentProcess()
    {
        return $this->response(Status::OK, array_keys(AoiAWD::getInstance()->getLogReceiver()->getCurrentProcess()));
    }

    public function actionPing()
    {
        return $this->response(Status::OK);
    }

    private function db()
    {
        return DBHelper::getDB();
    }
}
