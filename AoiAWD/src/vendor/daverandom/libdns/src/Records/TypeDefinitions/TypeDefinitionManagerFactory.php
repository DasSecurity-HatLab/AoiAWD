<?php declare(strict_types=1);
/**
 * Creates TypeDefinitionManager objects
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
 * Creates TypeDefinitionManager objects
 *
 * @category LibDNS
 * @package TypeDefinitions
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class TypeDefinitionManagerFactory
{
    /**
     * Create a new TypeDefinitionManager object
     *
     * @return \LibDNS\Records\TypeDefinitions\TypeDefinitionManager
     */
    public function create(): TypeDefinitionManager
    {
        return new TypeDefinitionManager(new TypeDefinitionFactory, new FieldDefinitionFactory);
    }
}
