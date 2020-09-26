<?php

namespace Amp\Dns\Internal;

use Amp\Dns\DnsException;
use Amp\Promise;
use Amp\Success;
use LibDNS\Decoder\DecoderFactory;
use LibDNS\Encoder\EncoderFactory;
use LibDNS\Messages\Message;
use function Amp\call;

/** @internal */
class UdpSocket extends Socket
{
    /** @var \LibDNS\Encoder\Encoder */
    private $encoder;

    /** @var \LibDNS\Decoder\Decoder */
    private $decoder;

    public static function connect(string $uri): Promise
    {
        if (!$socket = @\stream_socket_client($uri, $errno, $errstr, 0, STREAM_CLIENT_ASYNC_CONNECT)) {
            throw new DnsException(\sprintf(
                "Connection to %s failed: [Error #%d] %s",
                $uri,
                $errno,
                $errstr
            ));
        }

        return new Success(new self($socket));
    }

    protected function __construct($socket)
    {
        parent::__construct($socket);

        $this->encoder = (new EncoderFactory)->create();
        $this->decoder = (new DecoderFactory)->create();
    }

    protected function send(Message $message): Promise
    {
        $data = $this->encoder->encode($message);
        return $this->write($data);
    }

    protected function receive(): Promise
    {
        return call(function () {
            $data = yield $this->read();

            if ($data === null) {
                throw new DnsException("Reading from the server failed");
            }

            return $this->decoder->decode($data);
        });
    }

    public function isAlive(): bool
    {
        return true;
    }
}
