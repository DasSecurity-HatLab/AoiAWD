<?php declare(strict_types=1);
/**
 * Holds data about how the RDATA sections of known resource record types are structured
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

use \LibDNS\Records\ResourceTypes;
use \LibDNS\Records\Types\Types;
use \LibDNS\Records\Types\DomainName;

/**
 * Holds data about how the RDATA sections of known resource record types are structured
 *
 * @category LibDNS
 * @package TypeDefinitions
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class TypeDefinitionManager
{
    /**
     * @var array[] How the RDATA sections of known resource record types are structured
     */
    private $definitions = [];

    /**
     * @var array Cache of created definitions
     */
    private $typeDefs = [];

    /**
     * @var \LibDNS\Records\TypeDefinitions\TypeDefinitionFactory
     */
    private $typeDefFactory;

    /**
     * @var \LibDNS\Records\TypeDefinitions\FieldDefinitionFactory
     */
    private $fieldDefFactory;

    /**
     * Constructor
     *
     * @param \LibDNS\Records\TypeDefinitions\TypeDefinitionFactory $typeDefFactory
     * @param \LibDNS\Records\TypeDefinitions\FieldDefinitionFactory $fieldDefFactory
     */
    public function __construct(TypeDefinitionFactory $typeDefFactory, FieldDefinitionFactory $fieldDefFactory)
    {
        $this->typeDefFactory = $typeDefFactory;
        $this->fieldDefFactory = $fieldDefFactory;

        $this->setDefinitions();
    }

    /**
     * Set the internal definitions structure
     */
    private function setDefinitions()
    {
        // This is defined in a method because PHP doesn't let you define properties with
        // expressions at the class level. If anyone has a better way to do this I am open
        // to any and all suggestions.

        $this->definitions = [
            ResourceTypes::A => [ // RFC 1035
                'address' => Types::IPV4_ADDRESS,
            ],
            ResourceTypes::AAAA  => [ // RFC 3596
                'address' => Types::IPV6_ADDRESS,
            ],
            ResourceTypes::AFSDB => [ // RFC 1183
                'subtype'  => Types::SHORT,
                'hostname' => Types::DOMAIN_NAME,
            ],
            ResourceTypes::CAA => [ // RFC 6844
                'flags' => Types::DOMAIN_NAME,
                'tag'   => Types::CHARACTER_STRING,
                'value' => Types::ANYTHING,
            ],
            ResourceTypes::CERT => [ // RFC 4398
                'type'        => Types::SHORT,
                'key-tag'     => Types::SHORT,
                'algorithm'   => Types::CHAR,
                'certificate' => Types::ANYTHING,
            ],
            ResourceTypes::CNAME => [ // RFC 1035
                'cname' => Types::DOMAIN_NAME,
            ],
            ResourceTypes::DHCID => [ // RFC 4701
                'identifier-type' => Types::SHORT,
                'digest-type'     => Types::CHAR,
                'digest'          => Types::ANYTHING,
            ],
            ResourceTypes::DLV => [ // RFC 4034
                'key-tag'     => Types::SHORT,
                'algorithm'   => Types::CHAR,
                'digest-type' => Types::CHAR,
                'digest'      => Types::ANYTHING,
            ],
            ResourceTypes::DNAME => [ // RFC 4034
                'target' => Types::DOMAIN_NAME,
            ],
            ResourceTypes::DNSKEY => [ // RFC 6672
                'flags'      => Types::SHORT,
                'protocol'   => Types::CHAR,
                'algorithm'  => Types::CHAR,
                'public-key' => Types::ANYTHING,
            ],
            ResourceTypes::DS => [ // RFC 4034
                'key-tag'     => Types::SHORT,
                'algorithm'   => Types::CHAR,
                'digest-type' => Types::CHAR,
                'digest'      => Types::ANYTHING,
            ],
            ResourceTypes::HINFO => [ // RFC 1035
                'cpu' => Types::CHARACTER_STRING,
                'os'  => Types::CHARACTER_STRING,
            ],
            ResourceTypes::ISDN => [ // RFC 1183
                'isdn-address' => Types::CHARACTER_STRING,
                'sa'           => Types::CHARACTER_STRING,
            ],
            ResourceTypes::KEY => [ // RFC 2535
                'flags'      => Types::SHORT,
                'protocol'   => Types::CHAR,
                'algorithm'  => Types::CHAR,
                'public-key' => Types::ANYTHING,
            ],
            ResourceTypes::KX => [ // RFC 2230
                'preference' => Types::SHORT,
                'exchange'   => Types::DOMAIN_NAME,
            ],
            ResourceTypes::LOC => [ // RFC 1876
                'version'              => Types::CHAR,
                'size'                 => Types::CHAR,
                'horizontal-precision' => Types::CHAR,
                'vertical-precision'   => Types::CHAR,
                'latitude'             => Types::LONG,
                'longitude'            => Types::LONG,
                'altitude'             => Types::LONG,
            ],
            ResourceTypes::MB => [ // RFC 1035
                'madname' => Types::DOMAIN_NAME,
            ],
            ResourceTypes::MD => [ // RFC 1035
                'madname' => Types::DOMAIN_NAME,
            ],
            ResourceTypes::MF => [ // RFC 1035
                'madname' => Types::DOMAIN_NAME,
            ],
            ResourceTypes::MG => [ // RFC 1035
                'mgmname' => Types::DOMAIN_NAME,
            ],
            ResourceTypes::MINFO => [ // RFC 1035
                'rmailbx' => Types::DOMAIN_NAME,
                'emailbx' => Types::DOMAIN_NAME,
            ],
            ResourceTypes::MR => [ // RFC 1035
                'newname' => Types::DOMAIN_NAME,
            ],
            ResourceTypes::MX => [ // RFC 1035
                'preference' => Types::SHORT,
                'exchange'   => Types::DOMAIN_NAME,
            ],
            ResourceTypes::NAPTR => [ // RFC 3403
                'order'       => Types::SHORT,
                'preference'  => Types::SHORT,
                'flags'       => Types::CHARACTER_STRING,
                'services'    => Types::CHARACTER_STRING,
                'regexp'      => Types::CHARACTER_STRING,
                'replacement' => Types::DOMAIN_NAME,
            ],
            ResourceTypes::NS => [ // RFC 1035
                'nsdname' => Types::DOMAIN_NAME,
            ],
            ResourceTypes::NULL => [ // RFC 1035
                'data' => Types::ANYTHING,
            ],
            ResourceTypes::PTR => [ // RFC 1035
                'ptrdname' => Types::DOMAIN_NAME,
            ],
            ResourceTypes::RP => [ // RFC 1183
                'mbox-dname' => Types::DOMAIN_NAME,
                'txt-dname'  => Types::DOMAIN_NAME,
            ],
            ResourceTypes::RT => [ // RFC 1183
                'preference'        => Types::SHORT,
                'intermediate-host' => Types::DOMAIN_NAME,
            ],
            ResourceTypes::SIG => [ // RFC 4034
                'type-covered'         => Types::SHORT,
                'algorithm'            => Types::CHAR,
                'labels'               => Types::CHAR,
                'original-ttl'         => Types::LONG,
                'signature-expiration' => Types::LONG,
                'signature-inception'  => Types::LONG,
                'key-tag'              => Types::SHORT,
                'signers-name'         => Types::DOMAIN_NAME,
                'signature'            => Types::ANYTHING,
            ],
            ResourceTypes::SOA => [ // RFC 1035
                'mname'      => Types::DOMAIN_NAME,
                'rname'      => Types::DOMAIN_NAME,
                'serial'     => Types::LONG,
                'refresh'    => Types::LONG,
                'retry'      => Types::LONG,
                'expire'     => Types::LONG,
                'minimum'    => Types::LONG,
            ],
            ResourceTypes::SPF => [ // RFC 4408
                'data+' => Types::CHARACTER_STRING,
            ],
            ResourceTypes::SRV => [ // RFC 2782
                'priority' => Types::SHORT,
                'weight'   => Types::SHORT,
                'port'     => Types::SHORT,
                'name'     => Types::DOMAIN_NAME | DomainName::FLAG_NO_COMPRESSION,
            ],
            ResourceTypes::TXT => [ // RFC 1035
                'txtdata+' => Types::CHARACTER_STRING,
            ],
            ResourceTypes::WKS => [ // RFC 1035
                'address'  => Types::IPV4_ADDRESS,
                'protocol' => Types::SHORT,
                'bit-map'  => Types::BITMAP,
            ],
            ResourceTypes::X25 => [ // RFC 1183
                'psdn-address' => Types::CHARACTER_STRING,
            ],
        ];
    }

    /**
     * Get a type definition for a record type if it is known
     *
     * @param int $recordType Resource type, can be indicated using the ResourceTypes enum
     * @return \LibDNS\Records\TypeDefinitions\TypeDefinition
     */
    public function getTypeDefinition(int $recordType)
    {
        if (!isset($this->typeDefs[$recordType])) {
            $definition = isset($this->definitions[$recordType]) ? $this->definitions[$recordType] : ['data' => Types::ANYTHING];
            $this->typeDefs[$recordType] = $this->typeDefFactory->create($this->fieldDefFactory, $definition);
        }

        return $this->typeDefs[$recordType];
    }

    /**
     * Register a custom type definition
     *
     * @param int $recordType Resource type, can be indicated using the ResourceTypes enum
     * @param int[]|\LibDNS\Records\TypeDefinitions\TypeDefinition $definition
     * @throws \InvalidArgumentException When the type definition is invalid
     */
    public function registerTypeDefinition(int $recordType, $definition)
    {
        if (!($definition instanceof TypeDefinition)) {
            if (!\is_array($definition)) {
                throw new \InvalidArgumentException('Definition must be an array or an instance of ' . __NAMESPACE__ . '\TypeDefinition');
            }

            $definition = $this->typeDefFactory->create($this->fieldDefFactory, $definition);
        }

        $this->typeDefs[$recordType] = $definition;
    }
}
