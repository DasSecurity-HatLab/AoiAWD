<?php

namespace Amp\Sync;

use Amp\Promise;

/**
 * A thread-safe, asynchronous mutex using the pthreads locking mechanism.
 *
 * Compatible with POSIX systems and Microsoft Windows.
 */
class ThreadedMutex implements Mutex {
    /** @var Internal\MutexStorage */
    private $mutex;

    /**
     * Creates a new threaded mutex.
     */
    public function __construct() {
        $this->mutex = new Internal\MutexStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function acquire(): Promise {
        return $this->mutex->acquire();
    }
}
