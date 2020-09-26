<?php

namespace Amp\File;

use Amp\Coroutine;
use Amp\Parallel\Worker;
use Amp\Parallel\Worker\Pool;
use Amp\Parallel\Worker\TaskException;
use Amp\Parallel\Worker\WorkerException;
use Amp\Promise;
use Amp\Success;
use function Amp\call;

class ParallelDriver implements Driver
{
    /**
     * @var \Amp\Parallel\Worker\Pool
     */
    private $pool;

    /**
     * @param \Amp\Parallel\Worker\Pool|null $pool
     */
    public function __construct(Pool $pool = null)
    {
        $this->pool = $pool ?: Worker\pool();
    }

    /**
     * {@inheritdoc}
     */
    public function open(string $path, string $mode): Promise
    {
        return call(function () use ($path, $mode) {
            $worker = $this->pool->getWorker();
            try {
                list($id, $size, $mode) = yield $worker->enqueue(new Internal\FileTask("fopen", [$path, $mode]));
            } catch (TaskException $exception) {
                throw new FilesystemException("Could not open file", $exception);
            } catch (WorkerException $exception) {
                throw new FilesystemException("Could not send open request to worker", $exception);
            }
            return new ParallelHandle($worker, $id, $path, $size, $mode);
        });
    }

    private function runFileTask(Internal\FileTask $task): \Generator
    {
        try {
            return yield $this->pool->enqueue($task);
        } catch (TaskException $exception) {
            throw new FilesystemException("The file operation failed", $exception);
        } catch (WorkerException $exception) {
            throw new FilesystemException("Could not send the file task to worker", $exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function unlink(string $path): Promise
    {
        return call(function () use ($path) {
            $result = yield from $this->runFileTask(new Internal\FileTask("unlink", [$path]));
            StatCache::clear($path);
            return $result;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function stat(string $path): Promise
    {
        if ($stat = StatCache::get($path)) {
            return new Success($stat);
        }

        return call(function () use ($path) {
            $stat = yield from $this->runFileTask(new Internal\FileTask("stat", [$path]));
            if (!empty($stat)) {
                StatCache::set($path, $stat);
            }
            return $stat;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function rename(string $from, string $to): Promise
    {
        return new Coroutine($this->runFileTask(new Internal\FileTask("rename", [$from, $to])));
    }

    /**
     * {@inheritdoc}
     */
    public function isfile(string $path): Promise
    {
        return call(function () use ($path) {
            $stat = yield $this->stat($path);
            if (empty($stat)) {
                return false;
            }
            if ($stat["mode"] & 0100000) {
                return true;
            }
            return false;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function isdir(string $path): Promise
    {
        return call(function () use ($path) {
            $stat = yield $this->stat($path);
            if (empty($stat)) {
                return false;
            }
            if ($stat["mode"] & 0040000) {
                return true;
            }
            return false;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function link(string $target, string $link): Promise
    {
        return new Coroutine($this->runFileTask(new Internal\FileTask("link", [$target, $link])));
    }

    /**
     * {@inheritdoc}
     */
    public function symlink(string $target, string $link): Promise
    {
        return new Coroutine($this->runFileTask(new Internal\FileTask("symlink", [$target, $link])));
    }

    /**
     * {@inheritdoc}
     */
    public function readlink(string $path): Promise
    {
        return new Coroutine($this->runFileTask(new Internal\FileTask("readlink", [$path])));
    }

    /**
     * {@inheritdoc}
     */
    public function mkdir(string $path, int $mode = 0777, bool $recursive = false): Promise
    {
        return new Coroutine($this->runFileTask(new Internal\FileTask("mkdir", [$path, $mode, $recursive])));
    }

    /**
     * {@inheritdoc}
     */
    public function scandir(string $path): Promise
    {
        return new Coroutine($this->runFileTask(new Internal\FileTask("scandir", [$path])));
    }

    /**
     * {@inheritdoc}
     */
    public function rmdir(string $path): Promise
    {
        return call(function () use ($path) {
            $result = yield from $this->runFileTask(new Internal\FileTask("rmdir", [$path]));
            StatCache::clear($path);
            return $result;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function chmod(string $path, int $mode): Promise
    {
        return new Coroutine($this->runFileTask(new Internal\FileTask("chmod", [$path, $mode])));
    }

    /**
     * {@inheritdoc}
     */
    public function chown(string $path, int $uid, int $gid): Promise
    {
        return new Coroutine($this->runFileTask(new Internal\FileTask("chown", [$path, $uid, $gid])));
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $path): Promise
    {
        return new Coroutine($this->runFileTask(new Internal\FileTask("exists", [$path])));
    }

    /**
     * {@inheritdoc}
     */
    public function size(string $path): Promise
    {
        return call(function () use ($path) {
            $stat = yield $this->stat($path);
            if (empty($stat)) {
                throw new FilesystemException("Specified path does not exist");
            }
            if ($stat["mode"] & 0100000) {
                return $stat["size"];
            }
            throw new FilesystemException("Specified path is not a regular file");
        });
    }

    /**
     * {@inheritdoc}
     */
    public function mtime(string $path): Promise
    {
        return call(function () use ($path) {
            $stat = yield $this->stat($path);
            if (empty($stat)) {
                throw new FilesystemException("Specified path does not exist");
            }
            return $stat["mtime"];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function atime(string $path): Promise
    {
        return call(function () use ($path) {
            $stat = yield $this->stat($path);
            if (empty($stat)) {
                throw new FilesystemException("Specified path does not exist");
            }
            return $stat["atime"];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function ctime(string $path): Promise
    {
        return call(function () use ($path) {
            $stat = yield $this->stat($path);
            if (empty($stat)) {
                throw new FilesystemException("Specified path does not exist");
            }
            return $stat["ctime"];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function lstat(string $path): Promise
    {
        return new Coroutine($this->runFileTask(new Internal\FileTask("lstat", [$path])));
    }

    /**
     * {@inheritdoc}
     */
    public function touch(string $path, int $time = null, int $atime = null): Promise
    {
        return new Coroutine($this->runFileTask(new Internal\FileTask("touch", [$path, $time, $atime])));
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $path): Promise
    {
        return new Coroutine($this->runFileTask(new Internal\FileTask("get", [$path])));
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $path, string $contents): Promise
    {
        return new Coroutine($this->runFileTask(new Internal\FileTask("put", [$path, $contents])));
    }
}
