<?php

namespace Amp\File;

use Amp\Coroutine;
use Amp\Deferred;
use Amp\File\Internal\UvPoll;
use Amp\Loop;
use Amp\Promise;
use Amp\Success;

class UvDriver implements Driver
{
    /** @var \Amp\Loop\Driver */
    private $driver;

    /** @var \UVLoop|resource Loop resource of type uv_loop or instance of \UVLoop. */
    private $loop;

    /** @var UvPoll */
    private $poll;

    /**
     * @param \Amp\Loop\Driver The currently active loop driver.
     *
     * @return bool Determines if this driver can be used based on the environment.
     */
    public static function isSupported(Loop\Driver $driver): bool
    {
        return $driver instanceof Loop\UvDriver;
    }

    /**
     * @param \Amp\Loop\UvDriver $driver
     */
    public function __construct(Loop\UvDriver $driver)
    {
        $this->driver = $driver;
        $this->loop = $driver->getHandle();
        $this->poll = new UvPoll;
    }

    /**
     * {@inheritdoc}
     */
    public function open(string $path, string $mode): Promise
    {
        $flags = $this->parseMode($mode);
        $chmod = ($flags & \UV::O_CREAT) ? 0644 : 0;

        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        $openArr = [$mode, $path, $deferred];
        \uv_fs_open($this->loop, $path, $flags, $chmod, function ($fh) use ($openArr) {
            if ($fh) {
                $this->onOpenHandle($fh, $openArr);
            } else {
                list(, $path, $deferred) = $openArr;
                $deferred->fail(new FilesystemException(
                    "Failed opening file handle to $path"
                ));
            }
        });

        return $deferred->promise();
    }

    private function parseMode(string $mode): int
    {
        $mode = \str_replace(['b', 't', 'e'], '', $mode);

        switch ($mode) {
            case "r":  return \UV::O_RDONLY;
            case "r+": return \UV::O_RDWR;
            case "w":  return \UV::O_WRONLY | \UV::O_CREAT;
            case "w+": return \UV::O_RDWR | \UV::O_CREAT;
            case "a":  return \UV::O_WRONLY | \UV::O_CREAT | \UV::O_APPEND;
            case "a+": return \UV::O_RDWR | \UV::O_CREAT | \UV::O_APPEND;
            case "x":  return \UV::O_WRONLY | \UV::O_CREAT | \UV::O_EXCL;
            case "x+": return \UV::O_RDWR | \UV::O_CREAT | \UV::O_EXCL;
            case "c":  return \UV::O_WRONLY | \UV::O_CREAT;
            case "c+": return \UV::O_RDWR | \UV::O_CREAT;

            default:
                throw new \Error('Invalid file mode');
        }
    }

    private function onOpenHandle($fh, array $openArr)
    {
        list($mode) = $openArr;

        if ($mode[0] === "w") {
            \uv_fs_ftruncate($this->loop, $fh, $length = 0, function ($fh) use ($openArr) {
                if ($fh) {
                    $this->finalizeHandle($fh, $size = 0, $openArr);
                } else {
                    list(, $path, $deferred) = $openArr;
                    $deferred->fail(new FilesystemException(
                        "Failed truncating file $path"
                    ));
                }
            });
        } else {
            \uv_fs_fstat($this->loop, $fh, function ($fh, $stat) use ($openArr) {
                if ($fh) {
                    StatCache::set($openArr[1], $stat);
                    $this->finalizeHandle($fh, $stat["size"], $openArr);
                } else {
                    list(, $path, $deferred) = $openArr;
                    $deferred->fail(new FilesystemException(
                        "Failed reading file size from open handle pointing to $path"
                    ));
                }
            });
        }
    }

    private function finalizeHandle($fh, $size, array $openArr)
    {
        list($mode, $path, $deferred) = $openArr;
        $handle = new UvHandle($this->driver, $this->poll, $fh, $path, $mode, $size);
        $deferred->resolve($handle);
    }

    /**
     * {@inheritdoc}
     */
    public function stat(string $path): Promise
    {
        if ($stat = StatCache::get($path)) {
            return new Success($stat);
        }

        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        \uv_fs_stat($this->loop, $path, function ($fh, $stat) use ($deferred, $path) {
            if (empty($fh)) {
                $stat = null;
            } else {
                // link is not a valid stat type but returned by the uv extension
                // change link to nlink
                if (isset($stat['link'])) {
                    $stat['nlink'] = $stat['link'];

                    unset($stat['link']);
                }

                StatCache::set($path, $stat);
            }

            $deferred->resolve($stat);
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $path): Promise
    {
        $deferred = new Deferred;

        $this->stat($path)->onResolve(function ($error, $result) use ($deferred) {
            $deferred->resolve((bool) $result);
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function isdir(string $path): Promise
    {
        $deferred = new Deferred;

        $this->stat($path)->onResolve(function ($error, $result) use ($deferred) {
            if ($result) {
                $deferred->resolve(!($result["mode"] & \UV::S_IFREG));
            } else {
                $deferred->resolve(false);
            }
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function isfile(string $path): Promise
    {
        $deferred = new Deferred;

        $this->stat($path)->onResolve(function ($error, $result) use ($deferred) {
            if ($result) {
                $deferred->resolve((bool) ($result["mode"] & \UV::S_IFREG));
            } else {
                $deferred->resolve(false);
            }
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function size(string $path): Promise
    {
        $deferred = new Deferred;

        $this->stat($path)->onResolve(function ($error, $result) use ($deferred) {
            if (empty($result)) {
                $deferred->fail(new FilesystemException(
                    "Specified path does not exist"
                ));
            } elseif (($result["mode"] & \UV::S_IFREG)) {
                $deferred->resolve($result["size"]);
            } else {
                $deferred->fail(new FilesystemException(
                    "Specified path is not a regular file"
                ));
            }
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function mtime(string $path): Promise
    {
        $deferred = new Deferred;

        $this->stat($path)->onResolve(function ($error, $result) use ($deferred) {
            if ($result) {
                $deferred->resolve($result["mtime"]);
            } else {
                $deferred->fail(new FilesystemException(
                    "Specified path does not exist"
                ));
            }
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function atime(string $path): Promise
    {
        $deferred = new Deferred;

        $this->stat($path)->onResolve(function ($error, $result) use ($deferred) {
            if ($result) {
                $deferred->resolve($result["atime"]);
            } else {
                $deferred->fail(new FilesystemException(
                    "Specified path does not exist"
                ));
            }
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function ctime(string $path): Promise
    {
        $deferred = new Deferred;

        $this->stat($path)->onResolve(function ($error, $result) use ($deferred) {
            if ($result) {
                $deferred->resolve($result["ctime"]);
            } else {
                $deferred->fail(new FilesystemException(
                    "Specified path does not exist"
                ));
            }
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function lstat(string $path): Promise
    {
        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        \uv_fs_lstat($this->loop, $path, function ($fh, $stat) use ($deferred) {
            if (empty($fh)) {
                $stat = null;
            }

            $deferred->resolve($stat);
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function symlink(string $target, string $link): Promise
    {
        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        \uv_fs_symlink($this->loop, $target, $link, \UV::S_IRWXU | \UV::S_IRUSR, function ($fh) use ($deferred) {
            $deferred->resolve((bool) $fh);
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function link(string $target, string $link): Promise
    {
        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        \uv_fs_link($this->loop, $target, $link, \UV::S_IRWXU | \UV::S_IRUSR, function ($fh) use ($deferred) {
            $deferred->resolve((bool) $fh);
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function readlink(string $path): Promise
    {
        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        \uv_fs_readlink($this->loop, $path, function ($fh, $target) use ($deferred) {
            if (!(bool) $fh) {
                $deferred->fail(new FilesystemException("Could not read symbolic link"));

                return;
            }

            $deferred->resolve($target);
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function rename(string $from, string $to): Promise
    {
        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        \uv_fs_rename($this->loop, $from, $to, function ($fh) use ($deferred, $from) {
            StatCache::clear($from);
            $deferred->resolve((bool) $fh);
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function unlink(string $path): Promise
    {
        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        \uv_fs_unlink($this->loop, $path, function ($fh) use ($deferred, $path) {
            StatCache::clear($path);
            $deferred->resolve((bool) $fh);
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function mkdir(string $path, int $mode = 0777, bool $recursive = false): Promise
    {
        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        if ($recursive) {
            $path = \str_replace("/", DIRECTORY_SEPARATOR, $path);
            $arrayPath = \explode(DIRECTORY_SEPARATOR, $path);
            $tmpPath = "";

            $callback = function () use (
                &$callback, &$arrayPath, &$tmpPath, $mode, $deferred
            ) {
                $tmpPath .= DIRECTORY_SEPARATOR . \array_shift($arrayPath);

                if (empty($arrayPath)) {
                    \uv_fs_mkdir($this->loop, $tmpPath, $mode, function ($fh) use ($deferred) {
                        $deferred->resolve((bool) $fh);
                    });
                } else {
                    $this->isdir($tmpPath)->onResolve(function ($error, $result) use (
                        $callback, $tmpPath, $mode
                    ) {
                        if ($result) {
                            $callback();
                        } else {
                            \uv_fs_mkdir($this->loop, $tmpPath, $mode, $callback);
                        }
                    });
                }
            };

            $callback();
        } else {
            \uv_fs_mkdir($this->loop, $path, $mode, function ($fh) use ($deferred) {
                $deferred->resolve((bool) $fh);
            });
        }

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function rmdir(string $path): Promise
    {
        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        \uv_fs_rmdir($this->loop, $path, function ($fh) use ($deferred, $path) {
            StatCache::clear($path);
            $deferred->resolve((bool) $fh);
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function scandir(string $path): Promise
    {
        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        \uv_fs_readdir($this->loop, $path, 0, function ($fh, $data) use ($deferred, $path) {
            if (empty($fh) && $data !== 0) {
                $deferred->fail(new FilesystemException("Failed reading contents from {$path}"));
            } elseif ($data === 0) {
                $deferred->resolve([]);
            } else {
                $deferred->resolve($data);
            }
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function chmod(string $path, int $mode): Promise
    {
        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        \uv_fs_chmod($this->loop, $path, $mode, function ($fh) use ($deferred) {
            $deferred->resolve((bool) $fh);
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function chown(string $path, int $uid, int $gid): Promise
    {
        // @TODO Return a failure in windows environments
        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        \uv_fs_chown($this->loop, $path, $uid, $gid, function ($fh) use ($deferred) {
            $deferred->resolve((bool) $fh);
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function touch(string $path, int $time = null, int $atime = null): Promise
    {
        $time = $time ?? \time();
        $atime = $atime ?? $time;

        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        \uv_fs_utime($this->loop, $path, $time, $atime, function () use ($deferred) {
            // The uv_fs_utime() callback does not receive any args at this time
            $deferred->resolve(true);
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $path): Promise
    {
        $promise = new Coroutine($this->doGet($path));
        $this->poll->listen($promise);

        return $promise;
    }

    private function doGet($path): \Generator
    {
        $promise = $this->doFsOpen($path, $flags = \UV::O_RDONLY, $mode = 0);
        if (!$fh = yield $promise) {
            throw new FilesystemException("Failed opening file handle: {$path}");
        }

        $deferred = new Deferred;

        $stat = yield $this->doFsStat($fh);

        if (empty($stat)) {
            $deferred->fail(new FilesystemException("stat operation failed on open file handle"));
        } elseif (!$stat["isfile"]) {
            \uv_fs_close($this->loop, $fh, function () use ($deferred) {
                $deferred->fail(new FilesystemException("cannot buffer contents: path is not a file"));
            });
        } else {
            $buffer = yield $this->doFsRead($fh, $offset = 0, $stat["size"]);

            if ($buffer === false) {
                \uv_fs_close($this->loop, $fh, function () use ($deferred) {
                    $deferred->fail(new FilesystemException("read operation failed on open file handle"));
                });
            } else {
                \uv_fs_close($this->loop, $fh, function () use ($deferred, $buffer) {
                    $deferred->resolve($buffer);
                });
            }
        }

        return yield $deferred->promise();
    }

    private function doFsOpen($path, $flags, $mode)
    {
        $deferred = new Deferred;

        \uv_fs_open($this->loop, $path, $flags, $mode, function ($fh) use ($deferred, $path) {
            $deferred->resolve($fh);
        });

        return $deferred->promise();
    }

    private function doFsStat($fh)
    {
        $deferred = new Deferred;

        \uv_fs_fstat($this->loop, $fh, function ($fh, $stat) use ($deferred) {
            if ($fh) {
                $stat["isdir"] = (bool) ($stat["mode"] & \UV::S_IFDIR);
                $stat["isfile"] = !$stat["isdir"];
                $deferred->resolve($stat);
            } else {
                $deferred->resolve();
            }
        });

        return $deferred->promise();
    }

    private function doFsRead($fh, $offset, $len)
    {
        $deferred = new Deferred;

        \uv_fs_read($this->loop, $fh, $offset, $len, function ($fh, $nread, $buffer) use ($deferred) {
            $deferred->resolve($nread < 0 ? false : $buffer);
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $path, string $contents): Promise
    {
        $promise = new Coroutine($this->doPut($path, $contents));
        $this->poll->listen($promise);

        return $promise;
    }

    private function doPut($path, $contents): \Generator
    {
        $flags = \UV::O_WRONLY | \UV::O_CREAT;
        $mode = \UV::S_IRWXU | \UV::S_IRUSR;

        $promise = $this->doFsOpen($path, $flags, $mode);

        if (!$fh = yield $promise) {
            throw new FilesystemException("Failed opening write file handle");
        }

        $deferred = new Deferred;
        $len = \strlen($contents);

        \uv_fs_write($this->loop, $fh, $contents, $offset = 0, function ($fh, $result) use ($deferred, $len) {
            \uv_fs_close($this->loop, $fh, function () use ($deferred, $result, $len) {
                if ($result < 0) {
                    $deferred->fail(new FilesystemException(\uv_strerror($result)));
                } else {
                    $deferred->resolve($len);
                }
            });
        });

        return yield $deferred->promise();
    }
}
