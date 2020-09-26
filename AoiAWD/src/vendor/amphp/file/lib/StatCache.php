<?php

namespace Amp\File;

use Amp\Loop;

class StatCache
{
    private static $cache = [];
    private static $timeouts = [];
    private static $ttl = 3;
    private static $now = null;

    private static function init()
    {
        self::$now = \time();

        $watcher = Loop::repeat(1000, function () {
            self::$now = $now = \time();
            foreach (self::$cache as $path => $expiry) {
                if ($now > $expiry) {
                    unset(
                        self::$cache[$path],
                        self::$timeouts[$path]
                    );
                } else {
                    break;
                }
            }
        });

        Loop::unreference($watcher);

        Loop::setState(self::class, new class($watcher) {
            private $watcher;
            private $driver;

            public function __construct(string $watcher)
            {
                $this->watcher = $watcher;
                $this->driver = Loop::get();
            }

            public function __destruct()
            {
                $this->driver->cancel($this->watcher);
            }
        });
    }

    public static function get(string $path)
    {
        return isset(self::$cache[$path]) ? self::$cache[$path] : null;
    }

    public static function set(string $path, array $stat)
    {
        if (self::$ttl <= 0) {
            return;
        }

        if (Loop::getState(self::class) === null) {
            self::init();
        }

        self::$cache[$path] = $stat;
        self::$timeouts[$path] = self::$now + self::$ttl;
    }

    public static function ttl(int $seconds)
    {
        self::$ttl = $seconds;
    }

    public static function clear(string $path = null)
    {
        if (isset($path)) {
            unset(
                self::$cache[$path],
                self::$timeouts[$path]
            );
        } else {
            self::$cache = [];
            self::$timeouts = [];
        }
    }
}
