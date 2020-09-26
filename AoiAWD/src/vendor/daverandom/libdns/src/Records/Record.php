<?php declare(strict_types=1);
/**
 * Represents a DNS record
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

use \LibDNS\Records\Types\DomainName;

/**
 * Represents a DNS record
 *
 * @category LibDNS
 * @package Records
 * @author Chris Wright <https://github.com/DaveRandom>
 */
abstract class Record
{
    /**
     * @var \LibDNS\Records\Types\TypeFactory
     */
    protected $typeFactory;

    /**
     * @var \LibDNS\Records\Types\DomainName
     */
    protected $name;

    /**
     * @var int
     */
    protected $type;

    /**
     * @var int
     */
    protected $class = ResourceClasses::IN;

    /**
     * Get the value of the record name field
     *
     * @return \LibDNS\Records\Types\DomainName
     */
    public function getName(): DomainName
    {
        return $this->name;
    }

    /**
     * Set the value of the record name field
     *
     * @param string|\LibDNS\Records\Types\DomainName $name
     * @throws \UnexpectedValueException When the supplied value is not a valid domain name
     */
    public function setName($name)
    {
        if (!($name instanceof DomainName)) {
            $name = $this->typeFactory->createDomainName((string)$name);
        }

        $this->name = $name;
    }

    /**
     * Get the value of the record type field
     *
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Get the value of the record class field
     *
     * @return int
     */
    public function getClass(): int
    {
        return $this->class;
    }

    /**
     * Set the value of the record class field
     *
     * @param int $class The new value, can be indicated using the ResourceClasses/ResourceQClasses enums
     * @throws \RangeException When the supplied value is outside the valid range 0 - 65535
     */
    public function setClass(int $class)
    {
        if ($class < 0 || $class > 65535) {
            throw new \RangeException('Record class must be in the range 0 - 65535');
        }

        $this->class = $class;
    }
}
