<?php declare(strict_types=1);
/**
 * Creates Decoder objects
 *
 * PHP version 5.4
 *
 * @category LibDNS
 * @package Decoder
 * @author Chris Wright <https://github.com/DaveRandom>
 * @copyright Copyright (c) Chris Wright <https://github.com/DaveRandom>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @version 2.0.0
 */
namespace LibDNS\Decoder;

use \LibDNS\Packets\PacketFactory;
use \LibDNS\Messages\MessageFactory;
use \LibDNS\Records\RecordCollectionFactory;
use \LibDNS\Records\QuestionFactory;
use \LibDNS\Records\ResourceBuilder;
use \LibDNS\Records\ResourceFactory;
use \LibDNS\Records\RDataBuilder;
use \LibDNS\Records\RDataFactory;
use \LibDNS\Records\Types\TypeBuilder;
use \LibDNS\Records\Types\TypeFactory;
use \LibDNS\Records\TypeDefinitions\TypeDefinitionManager;
use \LibDNS\Records\TypeDefinitions\TypeDefinitionFactory;
use \LibDNS\Records\TypeDefinitions\FieldDefinitionFactory;

/**
 * Creates Decoder objects
 *
 * @category LibDNS
 * @package Decoder
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class DecoderFactory
{
    /**
     * Create a new Decoder object
     *
     * @param \LibDNS\Records\TypeDefinitions\TypeDefinitionManager $typeDefinitionManager
     * @param bool $allowTrailingData
     * @return Decoder
     */
    public function create(TypeDefinitionManager $typeDefinitionManager = null, bool $allowTrailingData = true): Decoder
    {
        $typeBuilder = new TypeBuilder(new TypeFactory);

        return new Decoder(
            new PacketFactory,
            new MessageFactory(new RecordCollectionFactory),
            new QuestionFactory,
            new ResourceBuilder(
                new ResourceFactory,
                new RDataBuilder(
                    new RDataFactory,
                    $typeBuilder
                ),
                $typeDefinitionManager ?: new TypeDefinitionManager(
                    new TypeDefinitionFactory,
                    new FieldDefinitionFactory
                )
            ),
            $typeBuilder,
            new DecodingContextFactory,
            $allowTrailingData
        );
    }
}
