<?php

namespace Amp\Http\Server\Websocket;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Server;
use Amp\Http\Server\ServerObserver;
use Amp\Promise;

abstract class Websocket implements RequestHandler, ServerObserver
{
    /** @var Internal\Rfc6455Gateway */
    private $gateway;
    /** @var bool */
    private $onStartCalled = false;

    /**
     * Creates a responder that accepts websocket connections.
     */
    public function __construct()
    {
        $this->gateway = new Internal\Rfc6455Gateway($this);
    }

    /**
     * Respond to websocket handshake requests.
     *
     * If a websocket application doesn't wish to impose any special constraints on the
     * handshake it doesn't have to do anything in this method and all handshakes will
     * be automatically accepted.
     *
     * Return an instance of \Amp\Http\Server\Response to reject the websocket connection request.
     *
     * @param Request  $request The HTTP request that instigated the handshake
     * @param Response $response The switching protocol response for adding headers, etc.
     *
     * @return Response|\Amp\Promise|\Generator Return the given response to accept the
     *     connection or a new response object to deny the connection. May also return a
     *     promise or generator to run as a coroutine.
     */
    abstract public function onHandshake(Request $request, Response $response);

    /**
     * Invoked when the full two-way websocket upgrade completes.
     *
     * @param int     $clientId A unique (to the current process) identifier for this client
     * @param Request $request The HTTP request the instigated the connection.
     */
    abstract public function onOpen(int $clientId, Request $request);

    /**
     * Invoked when data messages arrive from the client.
     *
     * @param int     $clientId A unique (to the current process) identifier for this client
     * @param Message $message A stream of data received from the client
     */
    abstract public function onData(int $clientId, Message $message);

    /**
     * Invoked when the close handshake completes.
     *
     * @param int    $clientId A unique (to the current process) identifier for this client
     * @param int    $code The websocket code describing the close
     * @param string $reason The reason for the close (may be empty)
     */
    abstract public function onClose(int $clientId, int $code, string $reason);

    /**
     * Send a UTF-8 text message to the given client.
     *
     * @param string $data Data to send.
     * @param int    $clientId
     *
     * @return \Amp\Promise<int>
     */
    final public function send(string $data, int $clientId): Promise
    {
        return $this->gateway->send($data, false, $clientId);
    }

    /**
     * Send a binary message to the given client.
     *
     * @param string $data Data to send.
     * @param int    $clientId
     *
     * @return \Amp\Promise<int>
     */
    final public function sendBinary(string $data, int $clientId): Promise
    {
        return $this->gateway->send($data, true, $clientId);
    }

    /**
     * Broadcast a UTF-8 text message to all clients (except those given in the optional array).
     *
     * @param string $data Data to send.
     * @param int[]  $exceptIds List of IDs to exclude from the broadcast.
     *
     * @return \Amp\Promise<int>
     */
    final public function broadcast(string $data, array $exceptIds = []): Promise
    {
        return $this->gateway->broadcast($data, false, $exceptIds);
    }

    /**
     * Send a binary message to all clients (except those given in the optional array).
     *
     * @param string $data Data to send.
     * @param int[]  $exceptIds List of IDs to exclude from the broadcast.
     *
     * @return \Amp\Promise<int>
     */
    final public function broadcastBinary(string $data, array $exceptIds = []): Promise
    {
        return $this->gateway->broadcast($data, true, $exceptIds);
    }

    /**
     * Send a UTF-8 text message to a set of clients.
     *
     * @param string     $data Data to send.
     * @param int[]|null $clientIds Array of client IDs.
     *
     * @return \Amp\Promise<int>
     */
    final public function multicast(string $data, array $clientIds): Promise
    {
        return $this->gateway->multicast($data, false, $clientIds);
    }

    /**
     * Send a binary message to a set of clients.
     *
     * @param string     $data Data to send.
     * @param int[]|null $clientIds Array of client IDs.
     *
     * @return \Amp\Promise<int>
     */
    final public function multicastBinary(string $data, array $clientIds): Promise
    {
        return $this->gateway->multicast($data, true, $clientIds);
    }

    /**
     * Close the client connection with a code and UTF-8 string reason.
     *
     * @param int    $clientId
     * @param int    $code
     * @param string $reason
     */
    final public function close(int $clientId, int $code = Code::NORMAL_CLOSE, string $reason = '')
    {
        $this->gateway->close($clientId, $code, $reason);
    }

    /**
     * @param int $clientId
     *
     * @return array [
     *     'remote_address'      => string|null,
     *     'bytes_read'          => int,
     *     'bytes_sent'          => int,
     *     'frames_read'         => int,
     *     'frames_sent'         => int,
     *     'messages_read'       => int,
     *     'messages_sent'       => int,
     *     'connected_at'        => int,
     *     'closed_at'           => int,
     *     'close_code'          => int,
     *     'close_reason'        => string,
     *     'last_read_at'        => int,
     *     'last_send_at'        => int,
     *     'last_data_read_at'   => int,
     *     'last_data_sent_at'   => int,
     *     'compression_enabled' => bool,
     * ]
     */
    final public function getInfo(int $clientId): array
    {
        return $this->gateway->getInfo($clientId);
    }

    /**
     * @return int[] Array of client IDs.
     */
    final public function getClients(): array
    {
        return $this->gateway->getClients();
    }

    /** {@inheritdoc} */
    final public function handleRequest(Request $request): Promise
    {
        if (!$this->onStartCalled) {
            throw new \Error(\sprintf(
                'Can\'t handle WebSocket handshake, because %s::onStart() overrides %s::onStart() and didn\'t call its parent method.',
                \str_replace("\0", '@', \get_class($this)), // replace NUL-byte in anonymous class name
                self::class
            ));
        }

        return $this->gateway->handleRequest($request);
    }

    /**
     * @param int $size The maximum size a single message may be in bytes. Default is 2097152 (2MB).
     *
     * @throws \Error If the size is less than 1.
     */
    final public function setMessageSizeLimit(int $size)
    {
        $this->gateway->setOption('maxMessageSize', $size);
    }

    /**
     * @param int $bytes Maximum number of bytes per minute the endpoint can receive from the client.
     *     Default is 8388608 (8MB).
     *
     * @throws \Error If the number of bytes is less than 1.
     */
    final public function setBytesPerMinuteLimit(int $bytes)
    {
        $this->gateway->setOption('maxBytesPerMinute', $bytes);
    }

    /**
     * @param int $size The maximum size a single frame may be in bytes. Default is 2097152 (2MB).
     *
     * @throws \Error If the size is less than 1.
     */
    final public function setFrameSizeLimit(int $size)
    {
        $this->gateway->setOption('maxFrameSize', $size);
    }

    /**
     * @param int $count The maximum number of frames that can be received per second. Default is 100.
     *
     * @throws \Error If the count is less than 1.
     */
    final public function setFramesPerSecondLimit(int $count)
    {
        $this->gateway->setOption('maxFramesPerSecond', $count);
    }

    /**
     * @param int $bytes The number of bytes in outgoing message that will cause the endpoint to break the message into
     *     multiple frames. Default is 65527 (64k - 9 for frame overhead).
     *
     * @throws \Error
     */
    final public function setFrameSplitThreshold(int $bytes)
    {
        $this->gateway->setOption('autoFrameSize', $bytes);
    }

    /**
     * @param int $period The number of seconds a connection may be idle before a ping is sent to client. Default is 10.
     *
     * @throws \Error If the period is less than 1.
     */
    final public function setHeartbeatPeriod(int $period)
    {
        $this->gateway->setOption('heartbeatPeriod', $period);
    }

    /**
     * @param int $period The number of seconds to wait after sending a close frame to wait for the client to send
     *     the acknowledging close frame before being disconnected. Default is 3.
     *
     * @throws \Error If the period is less than 1.
     */
    final public function setClosePeriod(int $period)
    {
        $this->gateway->setOption('closePeriod', $period);
    }

    /**
     * @param int $limit The number of unanswered pings allowed before a client is disconnected. Default is 3.
     *
     * @throws \Error If the limit is less than 1.
     */
    final public function setQueuedPingLimit(int $limit)
    {
        $this->gateway->setOption('queuedPingLimit', $limit);
    }

    /**
     * @param bool $validate True to validate text frame data as UTF-8, false to skip validation. Default is true.
     */
    final public function setValidateUtf8(bool $validate)
    {
        $this->gateway->setOption('validateUtf8', $validate);
    }

    /**
     * @param bool $textOnly True to allow only text frames (no binary).
     */
    final public function setTextOnly(bool $textOnly)
    {
        $this->gateway->setOption('textOnly', $textOnly);
    }

    /**
     * Invoked when the server is starting.
     *
     * Server sockets have been opened, but are not yet accepting client connections. This method should be used to set
     * up any necessary state for responding to requests, including starting loop watchers such as timers.
     *
     * Note: Implementations overriding this method must always call the parent method.
     *
     * @param Server $server
     *
     * @return Promise
     */
    public function onStart(Server $server): Promise
    {
        $this->onStartCalled = true;

        return $this->gateway->onStart($server);
    }

    /**
     * Invoked when the server has initiated stopping.
     *
     * No further requests are accepted and any connected clients should be closed gracefully and any loop watchers
     * cancelled.
     *
     * Note: Implementations overriding this method must always call the parent method.
     *
     * @param Server $server
     *
     * @return Promise
     */
    public function onStop(Server $server): Promise
    {
        return $this->gateway->onStop($server);
    }
}
