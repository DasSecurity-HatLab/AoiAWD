<?php

namespace Amp\Http;

final class InvalidHeaderException extends \Exception
{
    /**
     * Thrown on header injection attempts.
     *
     * @param string $reason Reason that can be used as HTTP response reason.
     */
    public function __construct(string $reason)
    {
        parent::__construct($reason);
    }
}
