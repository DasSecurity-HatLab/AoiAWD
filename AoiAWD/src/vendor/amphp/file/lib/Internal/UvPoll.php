<?php

namespace Amp\File\Internal;

use Amp\CallableMaker;
use Amp\Loop;
use Amp\Promise;

class UvPoll
{
    use CallableMaker;

    /** @var string */
    private $watcher;

    /** @var int */
    private $requests = 0;

    /** @var callable */
    private $onDone;

    public function __construct()
    {
        $this->onDone = $this->callableFromInstanceMethod("done");

        $this->watcher = Loop::repeat(\PHP_INT_MAX / 2, function () {
            // do nothing, it's a dummy watcher
        });

        Loop::disable($this->watcher);

        Loop::setState(self::class, new class($this->watcher) {
            private $watcher;

            public function __construct(string $watcher)
            {
                $this->watcher = $watcher;
            }

            public function __destruct()
            {
                Loop::cancel($this->watcher);
            }
        });
    }

    public function listen(Promise $promise)
    {
        if ($this->requests++ === 0) {
            Loop::enable($this->watcher);
        }

        $promise->onResolve($this->onDone);
    }

    private function done()
    {
        if (--$this->requests === 0) {
            Loop::disable($this->watcher);
        }

        \assert($this->requests >= 0);
    }
}
