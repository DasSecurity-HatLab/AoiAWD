<?php

namespace Amp\Parallel\Sync;

use Amp\Failure;
use Amp\Promise;
use Amp\Success;
use Amp\Sync\PosixSemaphore;
use function Amp\call;

/**
 * A container object for sharing a value across contexts.
 *
 * A shared object is a container that stores an object inside shared memory.
 * The object can be accessed and mutated by any thread or process. The shared
 * object handle itself is serializable and can be sent to any thread or process
 * to give access to the value that is shared in the container.
 *
 * Because each shared object uses its own shared memory segment, it is much
 * more efficient to store a larger object containing many values inside a
 * single shared container than to use many small shared containers.
 *
 * Note that accessing a shared object is not atomic. Access to a shared object
 * should be protected with a mutex to preserve data integrity.
 *
 * When used with forking, the object must be created prior to forking for both
 * processes to access the synchronized object.
 *
 * @see http://php.net/manual/en/book.shmop.php The shared memory extension.
 * @see http://man7.org/linux/man-pages/man2/shmctl.2.html How shared memory works on Linux.
 * @see https://msdn.microsoft.com/en-us/library/ms810613.aspx How shared memory works on Windows.
 */
final class SharedMemoryParcel implements Parcel
{
    /** @var int The byte offset to the start of the object data in memory. */
    const MEM_DATA_OFFSET = 7;

    // A list of valid states the object can be in.
    const STATE_UNALLOCATED = 0;
    const STATE_ALLOCATED = 1;
    const STATE_MOVED = 2;
    const STATE_FREED = 3;

    /** @var string */
    private $id;

    /** @var int The shared memory segment key. */
    private $key;

    /** @var PosixSemaphore A semaphore for synchronizing on the parcel. */
    private $semaphore;

    /** @var int An open handle to the shared memory segment. */
    private $handle;

    /** @var int */
    private $initializer = 0;

    /**
     * @param string $id
     * @param mixed $value
     * @param int $size The initial size in bytes of the shared memory segment. It will automatically be expanded as
     *     necessary.
     * @param int $permissions Permissions to access the semaphore. Use file permission format specified as 0xxx.
     *
     * @return \Amp\Parallel\Sync\SharedMemoryParcel
     */
    public static function create(string $id, $value, int $size = 8192, int $permissions = 0600): self
    {
        $parcel = new self($id);
        $parcel->init($value, $size, $permissions);
        return $parcel;
    }

    /**
     * @param string $id
     *
     * @return \Amp\Parallel\Sync\SharedMemoryParcel
     */
    public static function use(string $id): self
    {
        $parcel = new self($id);
        $parcel->open();
        return $parcel;
    }

    /**
     * Creates a new local object container.
     *
     * The object given will be assigned a new object ID and will have a
     * reference to it stored in memory local to the thread.
     *
     * @param mixed $value       The value to store in the container.
     * @param int   $size        The number of bytes to allocate for the object.
     *                           If not specified defaults to 16384 bytes.
     * @param int   $permissions The access permissions to set for the object.
     *                           If not specified defaults to 0600.
     */
    private function __construct(string $id)
    {
        if (!\extension_loaded("shmop")) {
            throw new \Error(__CLASS__ . " requires the shmop extension.");
        }

        $this->id = $id;
        $this->key = self::makeKey($this->id);
    }

    /**
     * @param mixed $value
     * @param int   $size
     * @param int   $permissions
     */
    private function init($value, int $size = 8192, int $permissions = 0600)
    {
        $this->semaphore = PosixSemaphore::create($this->id, 1);
        $this->initializer = \getmypid();

        $this->memOpen($this->key, 'n', $permissions, $size + self::MEM_DATA_OFFSET);
        $this->setHeader(self::STATE_ALLOCATED, 0, $permissions);
        $this->wrap($value);
    }

    private function open()
    {
        $this->semaphore = PosixSemaphore::use($this->id);
        $this->memOpen($this->key, 'w', 0, 0);
    }

    /**
     * Checks if the object has been freed.
     *
     * Note that this does not check if the object has been destroyed; it only
     * checks if this handle has freed its reference to the object.
     *
     * @return bool True if the object is freed, otherwise false.
     */
    private function isFreed(): bool
    {
        // If we are no longer connected to the memory segment, check if it has
        // been invalidated.
        if ($this->handle !== null) {
            $this->handleMovedMemory();
            $header = $this->getHeader();
            return $header['state'] === static::STATE_FREED;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function unwrap(): Promise
    {
        if ($this->isFreed()) {
            return new Failure(new SharedMemoryException('The object has already been freed.'));
        }

        $header = $this->getHeader();

        // Make sure the header is in a valid state and format.
        if ($header['state'] !== self::STATE_ALLOCATED || $header['size'] <= 0) {
            new Failure(new SharedMemoryException('Shared object memory is corrupt.'));
        }

        // Read the actual value data from memory and unserialize it.
        $data = $this->memGet(self::MEM_DATA_OFFSET, $header['size']);
        return new Success(\unserialize($data));
    }

    /**
     * If the value requires more memory to store than currently allocated, a
     * new shared memory segment will be allocated with a larger size to store
     * the value in. The previous memory segment will be cleaned up and marked
     * for deletion. Other processes and threads will be notified of the new
     * memory segment on the next read attempt. Once all running processes and
     * threads disconnect from the old segment, it will be freed by the OS.
     */
    protected function wrap($value)
    {
        if ($this->isFreed()) {
            throw new SharedMemoryException('The object has already been freed.');
        }

        $serialized = \serialize($value);
        $size = \strlen($serialized);
        $header = $this->getHeader();

        /* If we run out of space, we need to allocate a new shared memory
           segment that is larger than the current one. To coordinate with other
           processes, we will leave a message in the old segment that the segment
           has moved and along with the new key. The old segment will be discarded
           automatically after all other processes notice the change and close
           the old handle.
        */
        if (\shmop_size($this->handle) < $size + self::MEM_DATA_OFFSET) {
            $this->key = $this->key < 0xffffffff ? $this->key + 1 : \mt_rand(0x10, 0xfffffffe);
            $this->setHeader(self::STATE_MOVED, $this->key, 0);

            $this->memDelete();
            \shmop_close($this->handle);

            $this->memOpen($this->key, 'n', $header['permissions'], $size * 2);
        }

        // Rewrite the header and the serialized value to memory.
        $this->setHeader(self::STATE_ALLOCATED, $size, $header['permissions']);
        $this->memSet(self::MEM_DATA_OFFSET, $serialized);
    }

    /**
     * {@inheritdoc}
     */
    public function synchronized(callable $callback): Promise
    {
        return call(function () use ($callback) {
            /** @var \Amp\Sync\Lock $lock */
            $lock = yield $this->semaphore->acquire();

            try {
                $result = yield call($callback, yield $this->unwrap());

                if ($result !== null) {
                    $this->wrap($result);
                }
            } finally {
                $lock->release();
            }

            return $result;
        });
    }


    /**
     * Frees the shared object from memory.
     *
     * The memory containing the shared value will be invalidated. When all
     * process disconnect from the object, the shared memory block will be
     * destroyed by the OS.
     */
    public function __destruct()
    {
        if ($this->initializer === 0 || $this->initializer !== \getmypid()) {
            return;
        }

        if ($this->isFreed()) {
            return;
        }

        // Invalidate the memory block by setting its state to FREED.
        $this->setHeader(static::STATE_FREED, 0, 0);

        // Request the block to be deleted, then close our local handle.
        $this->memDelete();
        \shmop_close($this->handle);
        $this->handle = null;

        $this->semaphore = null;
    }

    /**
     * Private method to prevent cloning.
     */
    private function __clone()
    {
    }

    /**
     * Private method to prevent serialization.
     */
    private function __sleep()
    {
    }

    /**
     * Updates the current memory segment handle, handling any moves made on the
     * data.
     */
    private function handleMovedMemory()
    {
        // Read from the memory block and handle moved blocks until we find the
        // correct block.
        while (true) {
            $header = $this->getHeader();

            // If the state is STATE_MOVED, the memory is stale and has been moved
            // to a new location. Move handle and try to read again.
            if ($header['state'] !== self::STATE_MOVED) {
                break;
            }

            \shmop_close($this->handle);
            $this->key = $header['size'];
            $this->memOpen($this->key, 'w', 0, 0);
        }
    }

    /**
     * Reads and returns the data header at the current memory segment.
     *
     * @return array An associative array of header data.
     */
    private function getHeader(): array
    {
        $data = $this->memGet(0, self::MEM_DATA_OFFSET);
        return \unpack('Cstate/Lsize/Spermissions', $data);
    }

    /**
     * Sets the header data for the current memory segment.
     *
     * @param int $state       An object state.
     * @param int $size        The size of the stored data, or other value.
     * @param int $permissions The permissions mask on the memory segment.
     */
    private function setHeader(int $state, int $size, int $permissions)
    {
        $header = \pack('CLS', $state, $size, $permissions);
        $this->memSet(0, $header);
    }

    /**
     * Opens a shared memory handle.
     *
     * @param int    $key         The shared memory key.
     * @param string $mode        The mode to open the shared memory in.
     * @param int    $permissions Process permissions on the shared memory.
     * @param int    $size        The size to crate the shared memory in bytes.
     */
    private function memOpen(int $key, string $mode, int $permissions, int $size)
    {
        $this->handle = @\shmop_open($key, $mode, $permissions, $size);
        if ($this->handle === false) {
            throw new SharedMemoryException('Failed to create shared memory block.');
        }
    }

    /**
     * Reads binary data from shared memory.
     *
     * @param int $offset The offset to read from.
     * @param int $size   The number of bytes to read.
     *
     * @return string The binary data at the given offset.
     */
    private function memGet(int $offset, int $size): string
    {
        $data = \shmop_read($this->handle, $offset, $size);
        if ($data === false) {
            throw new SharedMemoryException('Failed to read from shared memory block.');
        }
        return $data;
    }

    /**
     * Writes binary data to shared memory.
     *
     * @param int    $offset The offset to write to.
     * @param string $data   The binary data to write.
     */
    private function memSet(int $offset, string $data)
    {
        if (!\shmop_write($this->handle, $data, $offset)) {
            throw new SharedMemoryException('Failed to write to shared memory block.');
        }
    }

    /**
     * Requests the shared memory segment to be deleted.
     */
    private function memDelete()
    {
        if (!\shmop_delete($this->handle)) {
            throw new SharedMemoryException('Failed to discard shared memory block.');
        }
    }

    private static function makeKey(string $id): int
    {
        return \abs(\unpack("l", \md5($id, true))[1]);
    }
}
