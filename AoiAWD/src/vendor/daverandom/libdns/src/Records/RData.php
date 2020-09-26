<?php declare(strict_types=1);
/**
 * Represents the RDATA section of a resource record
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

use \LibDNS\Records\Types\Type;
use \LibDNS\Records\TypeDefinitions\TypeDefinition;

/**
 * Represents a data type comprising multiple simple types
 *
 * @category LibDNS
 * @package Records
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class RData implements \IteratorAggregate, \Countable
{
    /**
     * @var \LibDNS\Records\Types\Type[] The items that make up the complex type
     */
    private $fields = [];

    /**
     * @var \LibDNS\Records\TypeDefinitions\TypeDefinition Structural definition of the fields
     */
    private $typeDef;

    /**
     * Constructor
     *
     * @param \LibDNS\Records\TypeDefinitions\TypeDefinition $typeDef
     */
    public function __construct(TypeDefinition $typeDef)
    {
        $this->typeDef = $typeDef;
    }

    /**
     * Magic method for type coersion to string
     *
     * @return string
     */
    public function __toString()
    {
        if ($handler = $this->typeDef->getToStringFunction()) {
            $result = \call_user_func_array($handler, $this->fields);
        } else {
            $result = \implode(',', $this->fields);
        }

        return $result;
    }

    /**
     * Get the field indicated by the supplied index
     *
     * @param int $index
     * @return \LibDNS\Records\Types\Type
     * @throws \OutOfBoundsException When the supplied index does not refer to a valid field
     */
    public function getField(int $index)
    {
        if (!isset($this->fields[$index])) {
            throw new \OutOfBoundsException('Index ' . $index . ' does not refer to a valid field');
        }

        return $this->fields[$index];
    }

    /**
     * Set the field indicated by the supplied index
     *
     * @param int $index
     * @param \LibDNS\Records\Types\Type $value
     * @throws \InvalidArgumentException When the supplied index/value pair does not match the type definition
     */
    public function setField(int $index, Type $value)
    {
        if (!$this->typeDef->getFieldDefinition($index)->assertDataValid($value)) {
            throw new \InvalidArgumentException('The supplied value is not valid for the specified index');
        }

        $this->fields[$index] = $value;
    }

    /**
     * Get the field indicated by the supplied name
     *
     * @param string $name
     * @return \LibDNS\Records\Types\Type
     * @throws \OutOfBoundsException When the supplied name does not refer to a valid field
     */
    public function getFieldByName(string $name): Type
    {
        return $this->getField($this->typeDef->getFieldIndexByName($name));
    }

    /**
     * Set the field indicated by the supplied name
     *
     * @param string $name
     * @param \LibDNS\Records\Types\Type $value
     * @throws \OutOfBoundsException When the supplied name does not refer to a valid field
     * @throws \InvalidArgumentException When the supplied value does not match the type definition
     */
    public function setFieldByName(string $name, Type $value)
    {
        $this->setField($this->typeDef->getFieldIndexByName($name), $value);
    }

    /**
     * Get the structural definition of the fields
     *
     * @return \LibDNS\Records\TypeDefinitions\TypeDefinition
     */
    public function getTypeDefinition(): TypeDefinition
    {
        return $this->typeDef;
    }

    /**
     * Retrieve an iterator (IteratorAggregate interface)
     *
     * @return \Iterator
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->fields);
    }

    /**
     * Get the number of fields (Countable interface)
     *
     * @return int
     */
    public function count(): int
    {
        return \count($this->fields);
    }
}
