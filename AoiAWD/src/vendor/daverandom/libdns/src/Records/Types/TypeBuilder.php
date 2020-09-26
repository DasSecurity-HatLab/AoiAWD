<?php declare(strict_types=1);
/**
 * Builds Types from type definitions
 *
 * PHP version 5.4
 *
 * @category LibDNS
 * @package Types
 * @author Chris Wright <https://github.com/DaveRandom>
 * @copyright Copyright (c) Chris Wright <https://github.com/DaveRandom>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @version 2.0.0
 */
namespace LibDNS\Records\Types;

/**
 * Builds Types from type definitions
 *
 * @category LibDNS
 * @package Types
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class TypeBuilder
{
    /**
     * @var \LibDNS\Records\Types\TypeFactory
     */
    private $typeFactory;

    /**
     * Constructor
     *
     * @param \LibDNS\Records\Types\TypeFactory $typeFactory
     */
    public function __construct(TypeFactory $typeFactory)
    {
        $this->typeFactory = $typeFactory;
    }

    /**
     * Build a new Type object corresponding to a resource record type
     *
     * @param int $type Data type, can be indicated using the Types enum
     * @return \LibDNS\Records\Types\Type
     */
    public function build(int $type): Type
    {
        static $typeMap = [
            Types::ANYTHING         => 'createAnything',
            Types::BITMAP           => 'createBitMap',
            Types::CHAR             => 'createChar',
            Types::CHARACTER_STRING => 'createCharacterString',
            Types::DOMAIN_NAME      => 'createDomainName',
            Types::IPV4_ADDRESS     => 'createIPv4Address',
            Types::IPV6_ADDRESS     => 'createIPv6Address',
            Types::LONG             => 'createLong',
            Types::SHORT            => 'createShort',
        ];

        if (!isset($typeMap[$type])) {
            throw new \InvalidArgumentException('Invalid Type identifier ' . $type);
        }

        return $this->typeFactory->{$typeMap[$type]}();
    }
}
