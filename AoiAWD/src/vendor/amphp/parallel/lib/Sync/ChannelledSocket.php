<?php

namespace Amp\Parallel\Sync;

use Amp\ByteStream\ResourceInputStream;
use Amp\ByteStream\ResourceOutputStream;
use Amp\Promise;

final class ChannelledSocket implements Channel
{
    /** @var ChannelledStream */
    private $channel;

    /** @var ResourceInputStream */
    private $read;

    /** @var ResourceOutputStream */
    private $write;

    /**
     * @param resource $read Readable stream resource.
     * @param resource $write Writable stream resource.
     *
     * @throws \Error If a stream resource is not given for $resource.
     */
    public function __construct($read, $write)
    {
        $this->channel = new ChannelledStream(
            $this->read = new ResourceInputStream($read),
            $this->write = new ResourceOutputStream($write)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function receive(): Promise
    {
        return $this->channel->receive();
    }

    /**
     * {@inheritdoc}
     */
    public function send($data): Promise
    {
        return $this->channel->send($data);
    }

    public function unreference()
    {
        $this->read->unreference();
    }

    public function reference()
    {
        $this->read->reference();
    }

    /**
     * Closes the read and write resource streams.
     */
    public function close()
    {
        $this->read->close();
        $this->write->close();
    }
}
