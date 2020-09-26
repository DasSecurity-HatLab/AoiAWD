<?php declare(strict_types=1);
/**
 * Represents a DNS resource record
 *
 * PHP version 5.4
 *
 * @category LibDNS
 * @package Records
 * @author Chris Wright <https://github.com/DaveRandom>
 * @copyright Copyright (c) Chris Wright <https://github.com/DaveRandom>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @version 2.0.0
 */
namespace LibDNS\Records;

use \LibDNS\Records\Types\TypeFactory;

/**
 * Represents a DNS resource record
 *
 * @category LibDNS
 * @package Records
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class Resource extends Record
{
    /**
     * @var int Value of the resource's time-to-live property
     */
    private $ttl;

    /**
     * @var \LibDNS\Records\RData
     */
    private $data;

    /**
     * Constructor
     *
     * @param \LibDNS\Records\Types\TypeFactory $typeFactory
     * @param int $type Can be indicated using the ResourceTypes enum
     * @param \LibDNS\Records\RData $data
     */
    public function __construct(TypeFactory $typeFactory, int $type, RData $data)
    {
        $this->typeFactory = $typeFactory;
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * Get the value of the record TTL field
     *
     * @return int
     */
    public function getTTL(): int
    {
        return $this->ttl;
    }

    /**
     * Set the value of the record TTL field
     *
     * @param int $ttl The new value
     * @throws \RangeException When the supplied value is outside the valid range 0 - 4294967296
     */
    public function setTTL(int $ttl)
    {
        if ($ttl < 0 || $ttl > 4294967296) {
            throw new \RangeException('Record class must be in the range 0 - 4294967296');
        }

        $this->ttl = $ttl;
    }

    /**
     * Get the value of the resource data field
     *
     * @return \LibDNS\Records\RData
     */
    public function getData(): RData
    {
        return $this->data;
    }
}
