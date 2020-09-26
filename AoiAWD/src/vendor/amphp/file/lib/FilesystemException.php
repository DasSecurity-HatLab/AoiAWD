<?php

namespace Amp\File;

class FilesystemException extends \Exception
{
    public function __construct(string $message, \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
