<?php

namespace Amp\Sync;

use Amp\Promise;
use function Amp\call;

class SemaphoreMutex implements Mutex {
    /** @var \Amp\Sync\Semaphore */
    private $semaphore;

    /**
     * @param \Amp\Sync\Semaphore $semaphore A semaphore with a single lock.
     */
    public function __construct(Semaphore $semaphore) {
        $this->semaphore = $semaphore;
    }

    /** {@inheritdoc} */
    public function acquire(): Promise {
        return call(function () {
            /** @var \Amp\Sync\Lock $lock */
            $lock = yield $this->semaphore->acquire();
            if ($lock->getId() !== 0) {
                $lock->release();
                throw new \Error("Cannot use a semaphore with more than a single lock");
            }
            return $lock;
        });
    }
}
