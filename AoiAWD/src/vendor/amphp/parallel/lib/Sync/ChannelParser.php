<?php

namespace Amp\Parallel\Sync;

use Amp\Parser\Parser;

final class ChannelParser extends Parser
{
    const HEADER_LENGTH = 5;

    /**
     * @param callable(mixed $data) Callback invoked when data is parsed.
     */
    public function __construct(callable $callback)
    {
        parent::__construct(self::parser($callback));
    }

    /**
     * @param mixed $data Data to encode to send over a channel.
     *
     * @return string Encoded data that can be parsed by this class.
     *
     * @throws \Amp\Parallel\Sync\SerializationException
     */
    public function encode($data): string
    {
        try {
            $data = \serialize($data);
        } catch (\Throwable $exception) {
            throw new SerializationException(
                "The given data cannot be sent because it is not serializable.",
                0,
                $exception
            );
        }

        return \pack("CL", 0, \strlen($data)) . $data;
    }

    /**
     * @param callable $push
     *
     * @return \Generator
     *
     * @throws \Amp\Parallel\Sync\ChannelException
     * @throws \Amp\Parallel\Sync\SerializationException
     */
    private static function parser(callable $push): \Generator
    {
        while (true) {
            $header = yield self::HEADER_LENGTH;
            $data = \unpack("Cprefix/Llength", $header);

            if ($data["prefix"] !== 0) {
                $data = $header . yield;
                throw new ChannelException("Invalid packet received: " . self::encodeUnprintableChars($data));
            }

            $data = yield $data["length"];

            // Attempt to unserialize the received data.
            try {
                $result = \unserialize($data);

                if ($result === false && $data !== \serialize(false)) {
                    throw new ChannelException("Received invalid data: " . self::encodeUnprintableChars($data));
                }
            } catch (\Throwable $exception) {
                throw new SerializationException("Exception thrown when unserializing data", 0, $exception);
            }

            $push($result);
        }
    }

    /**
     * @param string $data Binary data.
     *
     * @return string Unprintable characters encoded as \x##.
     */
    private static function encodeUnprintableChars(string $data): string
    {
        return \preg_replace_callback("/[^\x20-\x7e]/", function (array $matches) {
            return "\\x" . \dechex(\ord($matches[0]));
        }, $data);
    }
}
