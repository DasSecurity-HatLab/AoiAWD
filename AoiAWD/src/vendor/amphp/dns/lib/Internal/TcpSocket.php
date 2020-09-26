<?php

namespace Amp\Dns\Internal;

use Amp;
use Amp\Deferred;
use Amp\Dns\DnsException;
use Amp\Dns\TimeoutException;
use Amp\Loop;
use Amp\Parser\Parser;
use Amp\Promise;
use Amp\Success;
use LibDNS\Decoder\DecoderFactory;
use LibDNS\Encoder\EncoderFactory;
use LibDNS\Messages\Message;
use function Amp\call;

/** @internal */
class TcpSocket extends Socket
{
    /** @var \LibDNS\Encoder\Encoder */
    private $encoder;

    /** @var \SplQueue */
    private $queue;

    /** @var Parser */
    private $parser;

    /** @var bool */
    private $isAlive = true;

    public static function connect(string $uri, int $timeout = 5000): Promise
    {
        if (!$socket = @\stream_socket_client($uri, $errno, $errstr, 0, STREAM_CLIENT_ASYNC_CONNECT)) {
            throw new DnsException(\sprintf(
                "Connection to %s failed: [Error #%d] %s",
                $uri,
                $errno,
                $errstr
            ));
        }

        \stream_set_blocking($socket, false);

        return call(function () use ($uri, $socket, $timeout) {
            $deferred = new Deferred;

            $watcher = Loop::onWritable($socket, static function () use ($socket, $deferred) {
                $deferred->resolve(new self($socket));
            });

            try {
                return yield Promise\timeout($deferred->promise(), $timeout);
            } catch (Amp\TimeoutException $e) {
                throw new TimeoutException("Name resolution timed out, could not connect to server at $uri");
            } finally {
                Loop::cancel($watcher);
            }
        });
    }

    public static function parser(callable $callback): \Generator
    {
        $decoder = (new DecoderFactory)->create();

        while (true) {
            $length = yield 2;
            $length = \unpack("n", $length)[1];

            $rawData = yield $length;
            $callback($decoder->decode($rawData));
        }
    }

    protected function __construct($socket)
    {
        parent::__construct($socket);

        $this->encoder = (new EncoderFactory)->create();
        $this->queue = new \SplQueue;
        $this->parser = new Parser(self::parser([$this->queue, 'push']));
    }

    protected function send(Message $message): Promise
    {
        $data = $this->encoder->encode($message);
        $promise = $this->write(\pack("n", \strlen($data)) . $data);
        $promise->onResolve(function ($error) {
            if ($error) {
                $this->isAlive = false;
            }
        });

        return $promise;
    }

    protected function receive(): Promise
    {
        if ($this->queue->isEmpty()) {
            return call(function () {
                do {
                    $chunk = yield $this->read();

                    if ($chunk === null) {
                        $this->isAlive = false;
                        throw new DnsException("Reading from the server failed");
                    }

                    $this->parser->push($chunk);
                } while ($this->queue->isEmpty());

                return $this->queue->shift();
            });
        }

        return new Success($this->queue->shift());
    }

    public function isAlive(): bool
    {
        return $this->isAlive;
    }
}
