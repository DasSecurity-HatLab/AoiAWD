<?php declare(strict_types=1);
/**
 * Creates FieldDefinition objects
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

/**
 * Creates FieldDefinition objects
 *
 * @category LibDNS
 * @package TypeDefinitions
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class FieldDefinitionFactory
{
    /**
     * Create a new FieldDefinition object
     *
     * @param int $index
     * @param string $name
     * @param int $type
     * @param bool $allowsMultiple
     * @param int $minimumValues
     * @return \LibDNS\Records\TypeDefinitions\FieldDefinition
     */
    public function create(int $index, string $name, int $type, bool $allowsMultiple, int $minimumValues): FieldDefinition
    {
        return new FieldDefinition($index, $name, $type, $allowsMultiple, $minimumValues);
    }
}
