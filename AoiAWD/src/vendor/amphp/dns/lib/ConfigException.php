<?php

namespace Amp\Dns;

use Throwable;

/**
 * MUST be thrown in case the config can't be read and no fallback is available.
 */
class ConfigException extends DnsException
{
    public function __construct(string $message, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
