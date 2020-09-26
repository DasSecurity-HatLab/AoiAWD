<?php declare(strict_types=1);
/**
 * Creates TypeDefinition objects
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
 * Creates TypeDefinition objects
 *
 * @category LibDNS
 * @package TypeDefinitions
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class TypeDefinitionFactory
{
    /**
     * Create a new TypeDefinition object
     *
     * @param FieldDefinitionFactory $fieldDefinitionFactory
     * @param int[] $definition Structural definition of the fields
     * @return \LibDNS\Records\TypeDefinitions\TypeDefinition
     * @throws \InvalidArgumentException When the type definition is invalid
     */
    public function create(FieldDefinitionFactory $fieldDefinitionFactory, array $definition): TypeDefinition
    {
        return new TypeDefinition($fieldDefinitionFactory, $definition);
    }
}
