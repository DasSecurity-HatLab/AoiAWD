<?php declare(strict_types=1);
/**
 * Creates Type objects
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
 * Creates Type objects
 *
 * @category LibDNS
 * @package Types
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class TypeFactory
{
    /**
     * Create a new Anything object
     *
     * @param string $value
     * @return \LibDNS\Records\Types\Anything
     */
    public function createAnything(string $value = null)
    {
        return new Anything($value);
    }

    /**
     * Create a new BitMap object
     *
     * @param string $value
     * @return \LibDNS\Records\Types\BitMap
     */
    public function createBitMap(string $value = null)
    {
        return new BitMap($value);
    }

    /**
     * Create a new Char object
     *
     * @param int $value
     * @return \LibDNS\Records\Types\Char
     */
    public function createChar(int $value = null)
    {
        return new Char((string)$value);
    }

    /**
     * Create a new CharacterString object
     *
     * @param string $value
     * @return \LibDNS\Records\Types\CharacterString
     */
    public function createCharacterString(string $value = null)
    {
        return new CharacterString($value);
    }

    /**
     * Create a new DomainName object
     *
     * @param string|string[] $value
     * @return \LibDNS\Records\Types\DomainName
     */
    public function createDomainName($value = null)
    {
        return new DomainName($value);
    }

    /**
     * Create a new IPv4Address object
     *
     * @param string|int[] $value
     * @return \LibDNS\Records\Types\IPv4Address
     */
    public function createIPv4Address($value = null)
    {
        return new IPv4Address($value);
    }

    /**
     * Create a new IPv6Address object
     *
     * @param string|int[] $value
     * @return \LibDNS\Records\Types\IPv6Address
     */
    public function createIPv6Address($value = null)
    {
        return new IPv6Address($value);
    }

    /**
     * Create a new Long object
     *
     * @param int $value
     * @return \LibDNS\Records\Types\Long
     */
    public function createLong(int $value = null)
    {
        return new Long((string)$value);
    }

    /**
     * Create a new Short object
     *
     * @param int $value
     * @return \LibDNS\Records\Types\Short
     */
    public function createShort(int $value = null)
    {
        return new Short((string)$value);
    }
}
