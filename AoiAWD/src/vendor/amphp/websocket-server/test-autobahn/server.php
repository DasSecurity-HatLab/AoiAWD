<?php

require dirname(__DIR__) . "/vendor/autoload.php";

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Server;
use Amp\Http\Server\Websocket;
use Amp\Socket;
use Psr\Log\NullLogger;

Amp\Loop::run(function () {
    /* --- http://localhost:9001/ ------------------------------------------------------------------- */

    $websocket = new class extends Websocket\Websocket
    {
        public function onHandshake(Request $request, Response $response)
        {
            return $response;
        }

        public function onOpen(int $clientId, Request $request)
        {
        }

        public function onData(int $clientId, Websocket\Message $message)
        {
            if ($message->isBinary()) {
                yield $this->broadcastBinary(yield $message->buffer());
            } else {
                yield $this->broadcast(yield $message->buffer());
            }
        }

        public function onClose(int $clientId, int $code, string $reason)
        {
        }
    };

    $websocket->setBytesPerMinuteLimit(PHP_INT_MAX);
    $websocket->setFrameSizeLimit(PHP_INT_MAX);
    $websocket->setFramesPerSecondLimit(PHP_INT_MAX);
    $websocket->setMessageSizeLimit(PHP_INT_MAX);
    $websocket->setValidateUtf8(true);

    $server = new Server([Socket\listen("127.0.0.1:9001")], $websocket, new NullLogger);
    return $server->start();
});
