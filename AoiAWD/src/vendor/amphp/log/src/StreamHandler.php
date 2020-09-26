<?php

namespace Amp\Log;

use Amp\ByteStream\OutputStream;
use Monolog\Handler\AbstractProcessingHandler;
use Psr\Log\LogLevel;

final class StreamHandler extends AbstractProcessingHandler {
    /** @var OutputStream */
    private $stream;

    /** @var callable */
    private $onResolve;

    /** @var \Throwable|null */
    private $exception;

    /**
     * @param OutputStream $outputStream
     * @param string       $level
     * @param bool         $bubble
     */
    public function __construct(OutputStream $outputStream, string $level = LogLevel::DEBUG, bool $bubble = true) {
        parent::__construct($level, $bubble);
        $this->stream = $outputStream;

        $stream = &$this->stream;
        $exception = &$this->exception;
        $this->onResolve = static function (\Throwable $e = null) use (&$stream, &$exception) {
            if (!$stream) {
                return; // Prior write already failed, ignore this failure.
            }

            if ($e) {
                $stream = null;
                $exception = $e;

                throw $e;
            }
        };
    }

    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  array $record
     *
     * @return void
     */
    protected function write(array $record) {
        if ($this->exception) {
            throw $this->exception;
        }

        $this->stream->write((string) $record['formatted'])->onResolve($this->onResolve);
    }
}
