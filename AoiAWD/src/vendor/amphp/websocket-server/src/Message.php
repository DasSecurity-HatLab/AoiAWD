<?php

namespace Amp\Http\Server\Websocket;

use Amp\ByteStream\InputStream;
use Amp\ByteStream\Payload;

final class Message extends Payload
{
    /** @var bool */
    private $binary;

    public function __construct(InputStream $stream, bool $binary)
    {
        parent::__construct($stream);

        $this->binary = $binary;
    }

    /**
     * @return bool Signals whether the message is binary, false if it is UTF-8 text.
     *
     * @see isText
     */
    public function isBinary(): bool
    {
        return $this->binary;
    }

    /**
     * @return bool Signals whether the message is text, false if it is binary.
     *
     * @see isBinary
     */
    public function isText(): bool
    {
        return !$this->binary;
    }
}
