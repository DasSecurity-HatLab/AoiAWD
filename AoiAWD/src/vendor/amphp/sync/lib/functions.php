<?php

namespace Amp\Sync;

use Amp\Promise;
use function Amp\call;

/**
 * Invokes the given callback while maintaining a lock from the provided mutex. The lock is automatically released after
 * invoking the callback or once the promise returned by the callback is resolved. If the callback returns a Generator,
 * it will be run as a coroutine. See Amp\call().
 *
 * @param \Amp\Sync\Mutex $mutex
 * @param callable $callback
 * @param array ...$args
 *
 * @return \Amp\Promise Resolves with the return value of the callback.
 */
function synchronized(Mutex $mutex, callable $callback, ...$args): Promise {
    return call(function () use ($mutex, $callback, $args) {
        /** @var \Amp\Sync\Lock $lock */
        $lock = yield $mutex->acquire();

        try {
            return yield call($callback, ...$args);
        } finally {
            $lock->release();
        }
    });
}
