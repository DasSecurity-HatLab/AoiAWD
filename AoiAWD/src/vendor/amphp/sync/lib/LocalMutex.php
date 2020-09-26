<?php

namespace Amp\Sync;

use Amp\CallableMaker;
use Amp\Deferred;
use Amp\Promise;
use Amp\Success;

class LocalMutex implements Mutex {
    use CallableMaker;

    /** @var bool */
    private $locked = false;

    /** @var \Amp\Deferred[] */
    private $queue = [];

    /** @var callable */
    private $release;

    public function __construct() {
        $this->release = $this->callableFromInstanceMethod("release");
    }

    /** {@inheritdoc} */
    public function acquire(): Promise {
        if (!$this->locked) {
            $this->locked = true;
            return new Success(new Lock(0, $this->release));
        }

        $this->queue[] = $deferred = new Deferred;
        return $deferred->promise();
    }

    private function release() {
        if (!empty($this->queue)) {
            $deferred = \array_shift($this->queue);
            $deferred->resolve(new Lock(0, $this->release));
            return;
        }

        $this->locked = false;
    }
}
