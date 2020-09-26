<?php

namespace Amp\File;

use Amp\Loop;
use Amp\Promise;

const LOOP_STATE_IDENTIFIER = Driver::class;

/**
 * Retrieve the application-wide filesystem instance.
 *
 * @param \Amp\File\Driver $driver Use the specified object as the application-wide filesystem instance
 * @return \Amp\File\Driver
 */
function filesystem(Driver $driver = null): Driver
{
    if ($driver === null) {
        $driver = Loop::getState(LOOP_STATE_IDENTIFIER);
        if ($driver) {
            return $driver;
        }

        $driver = driver();
    }

    if (\defined("AMP_WORKER") && $driver instanceof ParallelDriver) {
        throw new \Error("Cannot use the parallel driver within a worker");
    }

    Loop::setState(LOOP_STATE_IDENTIFIER, $driver);
    return $driver;
}

/**
 * Create a new filesystem driver best-suited for the current environment.
 *
 * @return \Amp\File\Driver
 */
function driver(): Driver
{
    $driver = Loop::get();

    if (UvDriver::isSupported($driver)) {
        return new UvDriver($driver);
    }

    if (EioDriver::isSupported()) {
        return new EioDriver;
    }

    if (\defined("AMP_WORKER")) { // Prevent spawning infinite workers.
        return new BlockingDriver;
    }

    return new ParallelDriver;
}

/**
 * Open a handle for the specified path.
 *
 * @param string $path
 * @param string $mode
 * @return \Amp\Promise<\Amp\File\Handle>
 */
function open(string $path, string $mode): Promise
{
    return filesystem()->open($path, $mode);
}

/**
 * Execute a file stat operation.
 *
 * If the requested path does not exist the resulting Promise will resolve to NULL.
 * The returned Promise whould never resolve as a failure.
 *
 * @param string $path An absolute file system path
 * @return \Amp\Promise<array|null>
 */
function stat(string $path): Promise
{
    return filesystem()->stat($path);
}

/**
 * Does the specified path exist?
 *
 * This function should never resolve as a failure -- only a successfull bool value
 * indicating the existence of the specified path.
 *
 * @param string $path An absolute file system path
 * @return \Amp\Promise<bool>
 */
function exists(string $path): Promise
{
    return filesystem()->exists($path);
}

/**
 * Retrieve the size in bytes of the file at the specified path.
 *
 * If the path does not exist or is not a regular file this
 * function's returned Promise WILL resolve as a failure.
 *
 * @param string $path An absolute file system path
 * @fails \Amp\Files\FilesystemException If the path does not exist or is not a file
 * @return \Amp\Promise<int>
 */
function size(string $path): Promise
{
    return filesystem()->size($path);
}

/**
 * Does the specified path exist and is it a directory?
 *
 * If the path does not exist the returned Promise will resolve
 * to FALSE and will not reject with an error.
 *
 * @param string $path An absolute file system path
 * @return \Amp\Promise<bool>
 */
function isdir(string $path): Promise
{
    return filesystem()->isdir($path);
}

/**
 * Does the specified path exist and is it a file?
 *
 * If the path does not exist the returned Promise will resolve
 * to FALSE and will not reject with an error.
 *
 * @param string $path An absolute file system path
 * @return \Amp\Promise<bool>
 */
function isfile(string $path): Promise
{
    return filesystem()->isfile($path);
}

/**
 * Retrieve the path's last modification time as a unix timestamp.
 *
 * @param string $path An absolute file system path
 * @fails \Amp\Files\FilesystemException If the path does not exist
 * @return \Amp\Promise<int>
 */
function mtime(string $path): Promise
{
    return filesystem()->mtime($path);
}

/**
 * Retrieve the path's last access time as a unix timestamp.
 *
 * @param string $path An absolute file system path
 * @fails \Amp\Files\FilesystemException If the path does not exist
 * @return \Amp\Promise<int>
 */
function atime($path)
{
    return filesystem()->atime($path);
}

/**
 * Retrieve the path's creation time as a unix timestamp.
 *
 * @param string $path An absolute file system path
 * @fails \Amp\Files\FilesystemException If the path does not exist
 * @return \Amp\Promise<int>
 */
function ctime(string $path): Promise
{
    return filesystem()->ctime($path);
}

/**
 * Same as stat() except if the path is a link then the link's data is returned.
 *
 * If the requested path does not exist the resulting Promise will resolve to NULL.
 * The returned Promise whould never resolve as a failure.
 *
 * @param string $path An absolute file system path
 * @return \Amp\Promise<array|null>
 */
function lstat(string $path): Promise
{
    return filesystem()->lstat($path);
}

/**
 * Create a symlink $link pointing to the file/directory located at $original.
 *
 * @param string $original
 * @param string $link
 * @fails \Amp\Files\FilesystemException If the operation fails
 * @return \Amp\Promise<null>
 */
function symlink(string $original, string $link): Promise
{
    return filesystem()->symlink($original, $link);
}

/**
 * Create a hard link $link pointing to the file/directory located at $original.
 *
 * @param string $original
 * @param string $link
 * @fails \Amp\Files\FilesystemException If the operation fails
 * @return \Amp\Promise<null>
 */
function link(string $original, string $link): Promise
{
    return filesystem()->symlink($original, $link);
}

/**
 * Read the symlink at $path.
 *
 * @param string $path
 * @fails \Amp\Files\FilesystemException If the operation fails
 * @return \Amp\Promise<string>
 */
function readlink(string $path): Promise
{
    return filesystem()->readlink($path);
}

/**
 * Rename a file or directory.
 *
 * @param string $from
 * @param string $to
 * @fails \Amp\Files\FilesystemException If the operation fails
 * @return \Amp\Promise<null>
 */
function rename(string $from, string $to): Promise
{
    return filesystem()->rename($from, $to);
}

/**
 * Delete a file.
 *
 * @param string $path
 * @return \Amp\Promise<null>
 */
function unlink(string $path): Promise
{
    return filesystem()->unlink($path);
}

/**
 * Create a director.
 *
 * @param string $path
 * @param int $mode
 * @param bool $recursive
 * @return \Amp\Promise<null>
 */
function mkdir(string $path, int $mode = 0777, bool $recursive = false): Promise
{
    return filesystem()->mkdir($path, $mode, $recursive);
}

/**
 * Delete a directory.
 *
 * @param string $path
 * @return \Amp\Promise<null>
 */
function rmdir(string $path): Promise
{
    return filesystem()->rmdir($path);
}

/**
 * Retrieve an array of files and directories inside the specified path.
 *
 * Dot entries are not included in the resulting array (i.e. "." and "..").
 *
 * @param string $path
 * @return \Amp\Promise<array>
 */
function scandir(string $path): Promise
{
    return filesystem()->scandir($path);
}

/**
 * chmod a file or directory.
 *
 * @param string $path
 * @param int $mode
 * @return \Amp\Promise<null>
 */
function chmod(string $path, int $mode): Promise
{
    return filesystem()->chmod($path, $mode);
}

/**
 * chown a file or directory.
 *
 * @param string $path
 * @param int $uid -1 to ignore
 * @param int $gid -1 to ignore
 * @return \Amp\Promise<null>
 */
function chown(string $path, int $uid, int $gid = -1): Promise
{
    return filesystem()->chown($path, $uid, $gid);
}

/**
 * Update the access and modification time of the specified path.
 *
 * If the file does not exist it will be created automatically.
 *
 * @param string $path
 * @param int $time The touch time. If $time is not supplied, the current system time is used.
 * @param int $atime The access time. If $atime is not supplied, value passed to the $time parameter is used.
 * @return \Amp\Promise<null>
 */
function touch(string $path, int $time = null, int $atime = null): Promise
{
    return filesystem()->touch($path, $time, $atime);
}

/**
 * Buffer the specified file's contents.
 *
 * @param string $path The file path from which to buffer contents
 * @return \Amp\Promise<string>
 */
function get(string $path): Promise
{
    return filesystem()->get($path);
}

/**
 * Write the contents string to the specified path.
 *
 * @param string $path The file path to which to $contents should be written
 * @param string $contents The data to write to the specified $path
 * @return \Amp\Promise A promise resolving to the integer length written upon success
 */
function put(string $path, string $contents): Promise
{
    return filesystem()->put($path, $contents);
}
