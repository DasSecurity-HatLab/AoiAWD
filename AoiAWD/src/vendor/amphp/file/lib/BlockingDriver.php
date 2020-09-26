<?php

namespace Amp\File;

use Amp\Failure;
use Amp\Promise;
use Amp\Success;

class BlockingDriver implements Driver
{
    /**
     * {@inheritdoc}
     */
    public function open(string $path, string $mode): Promise
    {
        $mode = \str_replace(['b', 't', 'e'], '', $mode);

        switch ($mode) {
            case "r":
            case "r+":
            case "w":
            case "w+":
            case "a":
            case "a+":
            case "x":
            case "x+":
            case "c":
            case "c+":
                break;

            default:
                throw new \Error("Invalid file mode");
        }

        if (!$fh = \fopen($path, $mode . 'be')) {
            return new Failure(new FilesystemException(
                "Failed opening file handle"
            ));
        }

        return new Success(new BlockingHandle($fh, $path, $mode));
    }

    /**
     * {@inheritdoc}
     */
    public function stat(string $path): Promise
    {
        if ($stat = StatCache::get($path)) {
            return new Success($stat);
        } elseif ($stat = @\stat($path)) {
            StatCache::set($path, $stat);
            \clearstatcache(true, $path);
        } else {
            $stat = null;
        }

        return new Success($stat);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $path): Promise
    {
        if ($exists = @\file_exists($path)) {
            \clearstatcache(true, $path);
        }

        return new Success($exists);
    }

    /**
     * Retrieve the size in bytes of the file at the specified path.
     *
     * If the path does not exist or is not a regular file this
     * function's returned Promise WILL resolve as a failure.
     *
     * @param string $path An absolute file system path
     * @return \Amp\Promise<int>
     */
    public function size(string $path): Promise
    {
        if (!@\file_exists($path)) {
            return new Failure(new FilesystemException(
                "Path does not exist"
            ));
        }

        if (!@\is_file($path)) {
            return new Failure(new FilesystemException(
                "Path is not a regular file"
            ));
        }

        if (($size = @\filesize($path)) === false) {
            return new Failure(new FilesystemException(
                \error_get_last()["message"]
            ));
        }

        \clearstatcache(true, $path);
        return new Success($size);
    }

    /**
     * Does the specified path exist and is it a directory?
     *
     * If the path does not exist the returned Promise will resolve
     * to FALSE. It will NOT reject with an error.
     *
     * @param string $path An absolute file system path
     * @return \Amp\Promise<bool>
     */
    public function isdir(string $path): Promise
    {
        if (!@\file_exists($path)) {
            return new Success(false);
        }

        $isDir = @\is_dir($path);
        \clearstatcache(true, $path);

        return new Success($isDir);
    }

    /**
     * Does the specified path exist and is it a file?
     *
     * If the path does not exist the returned Promise will resolve
     * to FALSE. It will NOT reject with an error.
     *
     * @param string $path An absolute file system path
     * @return \Amp\Promise<bool>
     */
    public function isfile(string $path): Promise
    {
        if (!@\file_exists($path)) {
            return new Success(false);
        }

        $isFile = @\is_file($path);
        \clearstatcache(true, $path);

        return new Success($isFile);
    }

    /**
     * Retrieve the path's last modification time as a unix timestamp.
     *
     * @param string $path An absolute file system path
     * @return \Amp\Promise<int>
     */
    public function mtime(string $path): Promise
    {
        if (!@\file_exists($path)) {
            return new Failure(new FilesystemException(
                "Path does not exist"
            ));
        }

        $mtime = @\filemtime($path);
        \clearstatcache(true, $path);

        return new Success($mtime);
    }

    /**
     * Retrieve the path's last access time as a unix timestamp.
     *
     * @param string $path An absolute file system path
     * @return \Amp\Promise<int>
     */
    public function atime(string $path): Promise
    {
        if (!@\file_exists($path)) {
            return new Failure(new FilesystemException(
                "Path does not exist"
            ));
        }
        $atime = @\fileatime($path);
        \clearstatcache(true, $path);

        return new Success($atime);
    }

    /**
     * Retrieve the path's creation time as a unix timestamp.
     *
     * @param string $path An absolute file system path
     * @return \Amp\Promise<int>
     */
    public function ctime(string $path): Promise
    {
        if (!@\file_exists($path)) {
            return new Failure(new FilesystemException(
                "Path does not exist"
            ));
        }

        $ctime = @\filectime($path);
        \clearstatcache(true, $path);

        return new Success($ctime);
    }

    /**
     * {@inheritdoc}
     */
    public function lstat(string $path): Promise
    {
        if ($stat = @\lstat($path)) {
            \clearstatcache(true, $path);
        } else {
            $stat = null;
        }

        return new Success($stat);
    }

    /**
     * {@inheritdoc}
     */
    public function symlink(string $target, string $link): Promise
    {
        if (!@\symlink($target, $link)) {
            return new Failure(new FilesystemException("Could not create symbolic link"));
        }

        return new Success(true);
    }

    /**
     * {@inheritdoc}
     */
    public function link(string $target, string $link): Promise
    {
        if (!@\link($target, $link)) {
            return new Failure(new FilesystemException("Could not create hard link"));
        }

        return new Success(true);
    }

    /**
     * {@inheritdoc}
     */
    public function readlink(string $path): Promise
    {
        if (!($result = @\readlink($path))) {
            return new Failure(new FilesystemException("Could not read symbolic link"));
        }

        return new Success($result);
    }

    /**
     * {@inheritdoc}
     */
    public function rename(string $from, string $to): Promise
    {
        if (!@\rename($from, $to)) {
            return new Failure(new FilesystemException("Could not rename file"));
        }

        return new Success(true);
    }

    /**
     * {@inheritdoc}
     */
    public function unlink(string $path): Promise
    {
        StatCache::clear($path);
        return new Success((bool) @\unlink($path));
    }

    /**
     * {@inheritdoc}
     */
    public function mkdir(string $path, int $mode = 0777, bool $recursive = false): Promise
    {
        return new Success((bool) @\mkdir($path, $mode, $recursive));
    }

    /**
     * {@inheritdoc}
     */
    public function rmdir(string $path): Promise
    {
        StatCache::clear($path);
        return new Success((bool) @\rmdir($path));
    }

    /**
     * {@inheritdoc}
     */
    public function scandir(string $path): Promise
    {
        if (!@\is_dir($path)) {
            return new Failure(new FilesystemException(
                "Not a directory"
            ));
        } elseif ($arr = @\scandir($path)) {
            $arr = \array_values(\array_filter($arr, function ($el) {
                return !($el === "." || $el === "..");
            }));
            \clearstatcache(true, $path);
            return new Success($arr);
        }

        return new Failure(new FilesystemException(
            "Failed reading contents from {$path}"
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function chmod(string $path, int $mode): Promise
    {
        return new Success((bool) @\chmod($path, $mode));
    }

    /**
     * {@inheritdoc}
     */
    public function chown(string $path, int $uid, int $gid): Promise
    {
        if ($uid !== -1 && !@\chown($path, $uid)) {
            return new Failure(new FilesystemException(
                \error_get_last()["message"]
            ));
        }

        if ($gid !== -1 && !@\chgrp($path, $gid)) {
            return new Failure(new FilesystemException(
                \error_get_last()["message"]
            ));
        }

        return new Success;
    }

    /**
     * {@inheritdoc}
     */
    public function touch(string $path, int $time = null, int $atime = null): Promise
    {
        $time = $time ?? \time();
        $atime = $atime ?? $time;
        return new Success((bool) \touch($path, $time, $atime));
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $path): Promise
    {
        $result = @\file_get_contents($path);
        return ($result === false)
            ? new Failure(new FilesystemException(\error_get_last()["message"]))
            : new Success($result);
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $path, string $contents): Promise
    {
        $result = @\file_put_contents($path, $contents);
        return ($result === false)
            ? new Failure(new FilesystemException(\error_get_last()["message"]))
            : new Success($result);
    }
}
