<?php

namespace Amp\Socket;

/**
 * Thrown in case a second read operation is attempted while another receive operation is still pending.
 */
final class PendingReceiveError extends \Error
{
    public function __construct(
        string $message = "The previous receive operation must complete before accept can be called again",
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
