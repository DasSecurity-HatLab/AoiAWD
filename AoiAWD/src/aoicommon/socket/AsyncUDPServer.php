<?php
namespace aoicommon\socket;

use Amp\Loop;
use aoicommon\helper\CommonHelper;
use function Amp\Socket\endpoint;

class AsyncUDPServer
{
    private $udpServer;
    private $callback;
    private $logger;

    public function __construct($uri)
    {
        $this->udpServer = endpoint($uri);
        $this->logger = CommonHelper::getLogger(self::class);
    }

    public function start()
    {
        Loop::onReadable($this->udpServer->getResource(), [$this, "onReadable"]);
        $this->logger->info("Listening on {$this->udpServer->getAddress()}");
    }

    public function close()
    {
        $this->udpServer->close();
    }

    public function onReadable($watcher, $socket)
    {
        $data = @\stream_socket_recvfrom($socket, 65535, 0, $address);
        if ($data === false) {
            Loop::cancel($watcher);
            return;
        }
        ($this->callback)($data, $address);
    }

    public function setCallback(callable $callback)
    {
        $this->callback = $callback;
    }
}
