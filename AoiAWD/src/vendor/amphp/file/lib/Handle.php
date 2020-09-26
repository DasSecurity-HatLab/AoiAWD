<?php

namespace Amp\File;

use Amp\ByteStream\InputStream;
use Amp\ByteStream\OutputStream;
use Amp\Promise;

interface Handle extends InputStream, OutputStream
{
    const DEFAULT_READ_LENGTH = 8192;

    /**
     * Read $len bytes from the open file handle starting at $offset.
     *
     * @param int $length
     * @return \Amp\Promise<string|null>
     */
    public function read(int $length = self::DEFAULT_READ_LENGTH): Promise;

    /**
     * Write $data to the open file handle starting at $offset.
     *
     * @param string $data
     * @return \Amp\Promise<int>
     */
    public function write(string $data): Promise;

    /**
     * Write $data to the open file handle and close the handle once the write completes.
     *
     * @param string $data
     *
     * @return \Amp\Promise<int>
     */
    public function end(string $data = ""): Promise;

    /**
     * Close the file handle.
     *
     * Applications are not required to manually close handles -- they will
     * be unloaded automatically when the object is garbage collected.
     *
     * @return \Amp\Promise
     */
    public function close(): Promise;

    /**
     * Set the handle's internal pointer position.
     *
     * $whence values:
     *
     * SEEK_SET - Set position equal to offset bytes.
     * SEEK_CUR - Set position to current location plus offset.
     * SEEK_END - Set position to end-of-file plus offset.
     *
     * @param int $position
     * @param int $whence
     * @return \Amp\Promise<int> New offset position.
     */
    public function seek(int $position, int $whence = \SEEK_SET): Promise;

    /**
     * Return the current internal offset position of the file handle.
     *
     * @return int
     */
    public function tell(): int;

    /**
     * Test for "end-of-file" on the file handle.
     *
     * @return bool
     */
    public function eof(): bool;

    /**
     * Retrieve the path used when opening the file handle.
     *
     * @return string
     */
    public function path(): string;

    /**
     * Retrieve the mode used when opening the file handle.
     *
     * @return string
     */
    public function mode(): string;
}
