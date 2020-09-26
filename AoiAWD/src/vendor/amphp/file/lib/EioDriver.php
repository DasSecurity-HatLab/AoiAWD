<?php

namespace Amp\File;

use Amp\Deferred;
use Amp\Promise;
use Amp\Success;

class EioDriver implements Driver
{
    /** @var \Amp\File\Internal\EioPoll */
    private $poll;

    /**
     * @return bool Determines if this driver can be used based on the environment.
     */
    public static function isSupported(): bool
    {
        return \extension_loaded('eio');
    }

    public function __construct()
    {
        $this->poll = new Internal\EioPoll;
    }

    /**
     * {@inheritdoc}
     */
    public function open(string $path, string $mode): Promise
    {
        $flags = \EIO_O_NONBLOCK | \EIO_O_FSYNC | $this->parseMode($mode);
        $chmod = ($flags & \EIO_O_CREAT) ? 0644 : 0;

        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        $openArr = [$mode, $path, $deferred];
        \eio_open($path, $flags, $chmod, \EIO_PRI_DEFAULT, [$this, "onOpenHandle"], $openArr);

        return $deferred->promise();
    }

    private function parseMode(string $mode): int
    {
        $mode = \str_replace(['b', 't', 'e'], '', $mode);

        switch ($mode) {
            case 'r':  return \EIO_O_RDONLY;
            case 'r+': return \EIO_O_RDWR;
            case 'w':  return \EIO_O_WRONLY | \EIO_O_TRUNC | \EIO_O_CREAT;
            case 'w+': return \EIO_O_RDWR | \EIO_O_TRUNC | \EIO_O_CREAT;
            case 'a':  return \EIO_O_WRONLY | \EIO_O_APPEND | \EIO_O_CREAT;
            case 'a+': return \EIO_O_RDWR | \EIO_O_APPEND | \EIO_O_CREAT;
            case 'x':  return \EIO_O_WRONLY | \EIO_O_CREAT | \EIO_O_EXCL;
            case 'x+': return \EIO_O_RDWR | \EIO_O_CREAT | \EIO_O_EXCL;
            case 'c':  return \EIO_O_WRONLY | \EIO_O_CREAT;
            case 'c+': return \EIO_O_RDWR | \EIO_O_CREAT;

            default:
                throw new \Error('Invalid file mode');
        }
    }

    private function onOpenHandle($openArr, $result, $req)
    {
        list($mode, $path, $deferred) = $openArr;

        if ($result === -1) {
            $deferred->fail(new FilesystemException(\eio_get_last_error($req)));
        } elseif ($mode[0] === "a") {
            \array_unshift($openArr, $result);
            \eio_ftruncate($result, $offset = 0, \EIO_PRI_DEFAULT, [$this, "onOpenFtruncate"], $openArr);
        } else {
            \array_unshift($openArr, $result);
            \eio_fstat($result, \EIO_PRI_DEFAULT, [$this, "onOpenFstat"], $openArr);
        }
    }

    private function onOpenFtruncate($openArr, $result, $req)
    {
        list($fh, $mode, $path, $deferred) = $openArr;

        if ($result === -1) {
            $deferred->fail(new FilesystemException(\eio_get_last_error($req)));
        } else {
            $handle = new EioHandle($this->poll, $fh, $path, $mode, $size = 0);
            $deferred->resolve($handle);
        }
    }

    private function onOpenFstat($openArr, $result, $req)
    {
        list($fh, $mode, $path, $deferred) = $openArr;
        if ($result === -1) {
            $deferred->fail(new FilesystemException(\eio_get_last_error($req)));
        } else {
            StatCache::set($path, $result);
            $handle = new EioHandle($this->poll, $fh, $path, $mode, $result["size"]);
            $deferred->resolve($handle);
        }
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

        $priority = \EIO_PRI_DEFAULT;
        $data = [$deferred, $path];
        \eio_stat($path, $priority, [$this, "onStat"], $data);

        return $deferred->promise();
    }

    private function onStat($data, $result, $req)
    {
        list($deferred, $path) = $data;
        if ($result === -1) {
            $deferred->resolve(null);
        } else {
            StatCache::set($path, $result);
            $deferred->resolve($result);
        }
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
                $deferred->resolve(!($result["mode"] & \EIO_S_IFREG));
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
                $deferred->resolve((bool) ($result["mode"] & \EIO_S_IFREG));
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
            } elseif ($result["mode"] & \EIO_S_IFREG) {
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

        $priority = \EIO_PRI_DEFAULT;
        \eio_lstat($path, $priority, [$this, "onLstat"], $deferred);

        return $deferred->promise();
    }

    private function onLstat($deferred, $result, $req)
    {
        if ($result === -1) {
            $deferred->resolve(null);
        } else {
            $deferred->resolve($result);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function symlink(string $target, string $link): Promise
    {
        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        $priority = \EIO_PRI_DEFAULT;
        \eio_symlink($target, $link, $priority, [$this, "onGenericResult"], $deferred);

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function link(string $target, string $link): Promise
    {
        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        $priority = \EIO_PRI_DEFAULT;
        \eio_link($target, $link, $priority, [$this, "onGenericResult"], $deferred);

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function readlink(string $path): Promise
    {
        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        $priority = \EIO_PRI_DEFAULT;
        \eio_readlink($path, $priority, [$this, "onReadlink"], $deferred);

        return $deferred->promise();
    }

    private function onReadlink($deferred, $result, $req)
    {
        if ($result === -1) {
            $deferred->fail(new FilesystemException(\eio_get_last_error($req)));
        } else {
            $deferred->resolve($result);
        }
    }

    private function onGenericResult($deferred, $result, $req)
    {
        if ($result === -1) {
            $deferred->fail(new FilesystemException(\eio_get_last_error($req)));
        } else {
            $deferred->resolve(true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rename(string $from, string $to): Promise
    {
        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        $priority = \EIO_PRI_DEFAULT;
        \eio_rename($from, $to, $priority, [$this, "onGenericResult"], $deferred);

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function unlink(string $path): Promise
    {
        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        $priority = \EIO_PRI_DEFAULT;
        $data = [$deferred, $path];
        \eio_unlink($path, $priority, [$this, "onUnlink"], $data);

        return $deferred->promise();
    }

    private function onUnlink($data, $result, $req)
    {
        list($deferred, $path) = $data;

        if ($result === -1) {
            $deferred->fail(new FilesystemException(\eio_get_last_error($req)));
        } else {
            StatCache::clear($path);
            $deferred->resolve(true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mkdir(string $path, int $mode = 0777, bool $recursive = false): Promise
    {
        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        $priority = \EIO_PRI_DEFAULT;

        if ($recursive) {
            $path = \str_replace("/", DIRECTORY_SEPARATOR, $path);
            $arrayPath = \explode(DIRECTORY_SEPARATOR, $path);
            $tmpPath = "";

            $callback = function () use (
                &$callback, &$arrayPath, &$tmpPath, $mode, $priority, $deferred
            ) {
                $tmpPath .= DIRECTORY_SEPARATOR . \array_shift($arrayPath);

                if (empty($arrayPath)) {
                    \eio_mkdir($tmpPath, $mode, $priority, [$this, "onGenericResult"], $deferred);
                } else {
                    $this->isdir($tmpPath)->onResolve(function ($error, $result) use (
                        $callback, $tmpPath, $mode, $priority
                    ) {
                        if ($result) {
                            $callback();
                        } else {
                            \eio_mkdir($tmpPath, $mode, $priority, $callback);
                        }
                    });
                }
            };

            $callback();
        } else {
            \eio_mkdir($path, $mode, $priority, [$this, "onGenericResult"], $deferred);
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

        $priority = \EIO_PRI_DEFAULT;
        $data = [$deferred, $path];
        \eio_rmdir($path, $priority, [$this, "onRmdir"], $data);

        return $deferred->promise();
    }

    private function onRmdir($data, $result, $req)
    {
        list($deferred, $path) = $data;

        if ($result === -1) {
            $deferred->fail(new FilesystemException(\eio_get_last_error($req)));
        } else {
            StatCache::clear($path);
            $deferred->resolve(true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function scandir(string $path): Promise
    {
        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        $flags = \EIO_READDIR_STAT_ORDER | \EIO_READDIR_DIRS_FIRST;
        $priority = \EIO_PRI_DEFAULT;
        \eio_readdir($path, $flags, $priority, [$this, "onScandir"], $deferred);

        return $deferred->promise();
    }

    private function onScandir($deferred, $result, $req)
    {
        if ($result === -1) {
            $deferred->fail(new FilesystemException(\eio_get_last_error($req)));
        } else {
            $result = $result["names"];
            \sort($result);
            $deferred->resolve($result);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function chmod(string $path, int $mode): Promise
    {
        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        $priority = \EIO_PRI_DEFAULT;
        \eio_chmod($path, $mode, $priority, [$this, "onGenericResult"], $deferred);

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function chown(string $path, int $uid, int $gid): Promise
    {
        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        $priority = \EIO_PRI_DEFAULT;
        \eio_chown($path, $uid, $gid, $priority, [$this, "onGenericResult"], $deferred);

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

        $priority = \EIO_PRI_DEFAULT;
        \eio_utime($path, $atime, $time, $priority, [$this, "onGenericResult"], $deferred);

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $path): Promise
    {
        $flags = $flags = \EIO_O_RDONLY;
        $mode = 0;
        $priority = \EIO_PRI_DEFAULT;

        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        \eio_open($path, $flags, $mode, $priority, [$this, "onGetOpen"], $deferred);

        return $deferred->promise();
    }

    private function onGetOpen($deferred, $result, $req)
    {
        if ($result === -1) {
            $deferred->fail(new FilesystemException(\eio_get_last_error($req)));
        } else {
            $priority = \EIO_PRI_DEFAULT;
            \eio_fstat($result, $priority, [$this, "onGetFstat"], [$result, $deferred]);
        }
    }

    private function onGetFstat($fhAndPromisor, $result, $req)
    {
        list($fh, $deferred) = $fhAndPromisor;

        if ($result === -1) {
            $deferred->fail(new FilesystemException(\eio_get_last_error($req)));
            return;
        }

        $offset = 0;
        $length = $result["size"];
        $priority = \EIO_PRI_DEFAULT;
        \eio_read($fh, $length, $offset, $priority, [$this, "onGetRead"], $fhAndPromisor);
    }

    private function onGetRead($fhAndPromisor, $result, $req)
    {
        list($fh, $deferred) = $fhAndPromisor;

        \eio_close($fh);

        if ($result === -1) {
            $deferred->fail(new FilesystemException(\eio_get_last_error($req)));
        } else {
            $deferred->resolve($result);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $path, string $contents): Promise
    {
        $flags = \EIO_O_RDWR | \EIO_O_CREAT;
        $mode = \EIO_S_IRUSR | \EIO_S_IWUSR | \EIO_S_IXUSR;
        $priority = \EIO_PRI_DEFAULT;

        $deferred = new Deferred;
        $this->poll->listen($deferred->promise());

        $data = [$contents, $deferred];
        \eio_open($path, $flags, $mode, $priority, [$this, "onPutOpen"], $data);

        return $deferred->promise();
    }

    private function onPutOpen($data, $result, $req)
    {
        list($contents, $deferred) = $data;

        if ($result === -1) {
            $deferred->fail(new FilesystemException(\eio_get_last_error($req)));
        } else {
            $length = \strlen($contents);
            $offset = 0;
            $priority = \EIO_PRI_DEFAULT;
            $callback = [$this, "onPutWrite"];
            $fhAndPromisor = [$result, $deferred];
            \eio_write($result, $contents, $length, $offset, $priority, $callback, $fhAndPromisor);
        }
    }

    private function onPutWrite($fhAndPromisor, $result, $req)
    {
        list($fh, $deferred) = $fhAndPromisor;

        \eio_close($fh);

        if ($result === -1) {
            $deferred->fail(new FilesystemException(\eio_get_last_error($req)));
        } else {
            $deferred->resolve($result);
        }
    }
}
