<?php
namespace aoicommon\socket;

use Amp\Loop;
use aoicommon\helper\CommonHelper;
use function Amp\Socket\listen;

class AsyncTCPServer
{
    private $tcpServer;
    private $clients;
    private $callback;
    private $logger;
    private $cache = [];

    public function __construct($uri)
    {
        $this->tcpServer = listen($uri);
        $this->logger = CommonHelper::getLogger(self::class);
    }

    public function start()
    {
        Loop::onReadable($this->tcpServer->getResource(), [$this, "onAccept"]);
        $this->logger->info("Listening on {$this->tcpServer->getAddress()}");
    }

    public function close()
    {
        $this->tcpServer->close();
    }

    public function onAccept($watcher, $socket)
    {
        if (!$client = @\stream_socket_accept($socket, 0)) {
            Loop::cancel($watcher);
            $this->logger->alert("Main socket canceled!");
            return;
        }
        stream_set_blocking($client, false);
        $this->clients[] = $client;
        Loop::onReadable($client, [$this, "onClientReadable"]);
    }

    public function useStream($resId)
    {
        $this->cache[$resId][0] = false;
    }

    public function usePacket($resId)
    {
        $this->cache[$resId][0] = true;
    }

    public function setCallback(callable $callback)
    {
        $this->callback = $callback;
    }

    public function onClientReadable($watcher, $socket)
    {
        $data = @\fread($socket, 65535);
        $resId = (int)$socket;
        if (!isset($this->cache[$resId])) {
            $this->cache[$resId] = [true,  ''];
        }
        if (strlen($data) == 0) {
            Loop::cancel($watcher);
            unset($this->cache[$resId]);
            ($this->callback)(false, $socket);
            @\stream_socket_shutdown($socket, \STREAM_SHUT_RDWR);
            return;
        }
        if ($this->cache[$resId][0]) {
            $cache = &$this->cache[$resId][1];
            if (strlen($cache) > 1048576) {
                Loop::cancel($watcher);
                unset($this->cache[$resId]);
                @\stream_socket_shutdown($socket, \STREAM_SHUT_RDWR);
                return;
            }
            $div = explode("\n", $cache . $data);
            $cache = array_pop($div);
            if (count($div)) {
                foreach ($div as $packet) {
                    ($this->callback)($packet, $socket);
                }
            }
        } else {
            if (strlen($this->cache[$resId][1])) {
                ($this->callback)($this->cache[$resId][1], $socket);
                $this->cache[$resId][1] = '';
            }
            ($this->callback)($data, $socket);
        }
    }
}
