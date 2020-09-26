<?php
namespace aoiawd;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Websocket\Message;
use Amp\Http\Server\Websocket\Websocket;
use Amp\Success;

class WebsocketHandler extends Websocket
{

    static private $_self_instance;

    static private $prepareBroadcast = [];

    public function __construct()
    {
        parent::__construct();
        self::$_self_instance = $this;
    }

    static public function notifyAll($type)
    {
        if (!in_array($type, self::$prepareBroadcast)) {
            self::$prepareBroadcast[] = $type;
        }
    }

    static public function triggerNotify()
    {
        foreach (self::$prepareBroadcast as $type) {
            self::$_self_instance->broadcast(json_encode(['operation' => 'reload', 'type' => $type]));
        }
        self::$prepareBroadcast = [];
    }

    public function onHandshake(Request $request, Response $response)
    {
        return new Success($response);
    }

    public function onOpen(int $clientId, Request $request)
    { }

    public function onData(int $clientId, Message $message)
    { }

    public function onClose(int $clientId, int $code, string $reason)
    { }
}
