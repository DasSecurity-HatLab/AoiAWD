<?php declare(strict_types=1);
/**
 * Defines a field in a type
 *
 * PHP version 5.4
 *
 * @category LibDNS
 * @package TypeDefinitions
 * @author Chris Wright <https://github.com/DaveRandom>
 * @copyright Copyright (c) Chris Wright <https://github.com/DaveRandom>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @version 2.0.0
 */
namespace LibDNS\Records\TypeDefinitions;

use \LibDNS\Records\Types\Type;
use \LibDNS\Records\Types\Anything;
use \LibDNS\Records\Types\BitMap;
use \LibDNS\Records\Types\Char;
use \LibDNS\Records\Types\CharacterString;
use \LibDNS\Records\Types\DomainName;
use \LibDNS\Records\Types\IPv4Address;
use \LibDNS\Records\Types\IPv6Address;
use \LibDNS\Records\Types\Long;
use \LibDNS\Records\Types\Short;
use \LibDNS\Records\Types\Types;

/**
 * Defines a field in a type
 *
 * @category LibDNS
 * @package TypeDefinitions
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class FieldDefinition
{
    /**
     * @var int
     */
    private $index;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $type;

    /**
     * @var bool
     */
    private $allowsMultiple;

    /**
     * @var int
     */
    private $minimumValues;

    /**
     * Constructor
     *
     * @param int $index
     * @param string $name
     * @param int $type
     * @param bool $allowsMultiple
     * @param int $minimumValues
     */
    public function __construct(int $index, string $name, int $type, bool $allowsMultiple, int $minimumValues)
    {
        $this->index = $index;
        $this->name = $name;
        $this->type = $type;
        $this->allowsMultiple = $allowsMultiple;
        $this->minimumValues = $minimumValues;
    }

    /**
     * Get the index of the field in the containing type
     *
     * @return int
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * Get the name of the field
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the type of the field
     *
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Determine whether the field allows multiple values
     *
     * @return bool
     */
    public function allowsMultiple(): bool
    {
        return $this->allowsMultiple;
    }

    /**
     * Get the minimum number of values for the field
     *
     * @return int
     */
    public function getMinimumValues(): int
    {
        return $this->minimumValues;
    }

    /**
     * Assert that a Type object is valid for this field
     *
     * @param \LibDNS\Records\Types\Type
     * @return bool
     */
    public function assertDataValid(Type $value): bool
    {
        return (($this->type & Types::ANYTHING)         && $value instanceof Anything)
            || (($this->type & Types::BITMAP)           && $value instanceof BitMap)
            || (($this->type & Types::CHAR)             && $value instanceof Char)
            || (($this->type & Types::CHARACTER_STRING) && $value instanceof CharacterString)
            || (($this->type & Types::DOMAIN_NAME)      && $value instanceof DomainName)
            || (($this->type & Types::IPV4_ADDRESS)     && $value instanceof IPv4Address)
            || (($this->type & Types::IPV6_ADDRESS)     && $value instanceof IPv6Address)
            || (($this->type & Types::LONG)             && $value instanceof Long)
            || (($this->type & Types::SHORT)            && $value instanceof Short);
    }
}
