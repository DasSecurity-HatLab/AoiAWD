<?php

namespace Amp\Cache;

use Amp\Promise;

final class PrefixCache implements Cache {
    private $cache;
    private $keyPrefix;

    public function __construct(Cache $cache, string $keyPrefix) {
        $this->cache = $cache;
        $this->keyPrefix = $keyPrefix;
    }

    /**
     * Gets the specified key prefix.
     *
     * @return string
     */
    public function getKeyPrefix(): string {
        return $this->keyPrefix;
    }

    /** @inheritdoc */
    public function get(string $key): Promise {
        return $this->cache->get($this->keyPrefix . $key);
    }

    /** @inheritdoc */
    public function set(string $key, string $value, int $ttl = null): Promise {
        return $this->cache->set($this->keyPrefix . $key, $value, $ttl);
    }

    /** @inheritdoc */
    public function delete(string $key): Promise {
        return $this->cache->delete($this->keyPrefix . $key);
    }
}
