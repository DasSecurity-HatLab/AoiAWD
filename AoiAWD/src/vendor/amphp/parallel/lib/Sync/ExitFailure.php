<?php

namespace Amp\Parallel\Sync;

final class ExitFailure implements ExitResult
{
    /** @var string */
    private $type;

    /** @var string */
    private $message;

    /** @var int|string */
    private $code;

    /** @var array */
    private $trace;

    /** @var self|null */
    private $previous;

    public function __construct(\Throwable $exception)
    {
        $this->type = \get_class($exception);
        $this->message = $exception->getMessage();
        $this->code = $exception->getCode();
        $this->trace = $exception->getTraceAsString();

        if ($previous = $exception->getPrevious()) {
            $this->previous = new self($previous);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getResult()
    {
        throw $this->createException();
    }

    private function createException(): PanicError
    {
        $previous = $this->previous ? $this->previous->createException() : null;

        return new PanicError(
            $this->type,
            \sprintf(
                'Uncaught %s in worker with message "%s" and code "%s"; use %s::getPanicTrace() '
                    . 'for the stack trace in the context',
                $this->type,
                $this->message,
                $this->code,
                PanicError::class
            ),
            $this->trace,
            $previous
        );
    }
}
