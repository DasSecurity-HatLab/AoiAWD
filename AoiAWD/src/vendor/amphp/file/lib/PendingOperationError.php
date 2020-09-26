<?php

namespace Amp\File;

class PendingOperationError extends \Error
{
    public function __construct(
        string $message = "The previous file operation must complete before another can be started",
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
