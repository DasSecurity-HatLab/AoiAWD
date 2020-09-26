<?php

namespace Amp\Parallel\Sync;

use Amp\ByteStream\InputStream;
use Amp\ByteStream\OutputStream;
use Amp\ByteStream\StreamException;
use Amp\Promise;
use function Amp\call;

/**
 * An asynchronous channel for sending data between threads and processes.
 *
 * Supports full duplex read and write.
 */
final class ChannelledStream implements Channel
{
    /** @var \Amp\ByteStream\InputStream */
    private $read;

    /** @var \Amp\ByteStream\OutputStream */
    private $write;

    /** @var \SplQueue */
    private $received;

    /** @var \Amp\Parser\Parser */
    private $parser;

    /**
     * Creates a new channel from the given stream objects. Note that $read and $write can be the same object.
     *
     * @param \Amp\ByteStream\InputStream $read
     * @param \Amp\ByteStream\OutputStream $write
     */
    public function __construct(InputStream $read, OutputStream $write)
    {
        $this->read = $read;
        $this->write = $write;
        $this->received = new \SplQueue;
        $this->parser = new ChannelParser([$this->received, 'push']);
    }

    /**
     * {@inheritdoc}
     */
    public function send($data): Promise
    {
        return call(function () use ($data) {
            try {
                return yield $this->write->write($this->parser->encode($data));
            } catch (StreamException $exception) {
                throw new ChannelException("Sending on the channel failed. Did the context die?", 0, $exception);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function receive(): Promise
    {
        return call(function () {
            while ($this->received->isEmpty()) {
                try {
                    $chunk = yield $this->read->read();
                } catch (StreamException $exception) {
                    throw new ChannelException("Reading from the channel failed. Did the context die?", 0, $exception);
                }

                if ($chunk === null) {
                    throw new ChannelException("The channel closed unexpectedly. Did the context die?");
                }

                $this->parser->push($chunk);
            }

            return $this->received->shift();
        });
    }
}
