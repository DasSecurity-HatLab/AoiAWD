<?php declare(strict_types=1);
/**
 * Builds Resource objects of a specific type
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

use \LibDNS\Records\TypeDefinitions\TypeDefinitionManager;

/**
 * Builds Resource objects of a specific type
 *
 * @category LibDNS
 * @package Records
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class ResourceBuilder
{
    /**
     * @var \LibDNS\Records\ResourceFactory
     */
    private $resourceFactory;

    /**
     * @var \LibDNS\Records\RDataBuilder
     */
    private $rDataBuilder;

    /**
     * @var \LibDNS\Records\TypeDefinitions\TypeDefinitionManager
     */
    private $typeDefinitionManager;

    /**
     * Constructor
     *
     * @param \LibDNS\Records\ResourceFactory $resourceFactory
     * @param \LibDNS\Records\RDataBuilder $rDataBuilder
     * @param \LibDNS\Records\TypeDefinitions\TypeDefinitionManager $typeDefinitionManager
     */
    public function __construct(ResourceFactory $resourceFactory, RDataBuilder $rDataBuilder, TypeDefinitionManager $typeDefinitionManager)
    {
        $this->resourceFactory = $resourceFactory;
        $this->rDataBuilder = $rDataBuilder;
        $this->typeDefinitionManager = $typeDefinitionManager;
    }

    /**
     * Create a new Resource object
     *
     * @param int $type Type of the resource, can be indicated using the ResourceTypes enum
     * @return \LibDNS\Records\Resource
     */
    public function build(int $type): Resource
    {
        $typeDefinition = $this->typeDefinitionManager->getTypeDefinition($type);
        $rData = $this->rDataBuilder->build($typeDefinition);

        return $this->resourceFactory->create($type, $rData);
    }
}
