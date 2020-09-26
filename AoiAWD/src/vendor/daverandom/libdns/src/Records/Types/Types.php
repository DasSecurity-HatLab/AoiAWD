<?php declare(strict_types=1);
/**
 * Enumeration of simple data types
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

use \LibDNS\Enumeration;

/**
 * Enumeration of simple data types
 *
 * @category LibDNS
 * @package Types
 * @author Chris Wright <https://github.com/DaveRandom>
 */
final class Types extends Enumeration
{
    const ANYTHING         = 0b000000001;
    const BITMAP           = 0b000000010;
    const CHAR             = 0b000000100;
    const CHARACTER_STRING = 0b000001000;
    const DOMAIN_NAME      = 0b000010000;
    const IPV4_ADDRESS     = 0b000100000;
    const IPV6_ADDRESS     = 0b001000000;
    const LONG             = 0b010000000;
    const SHORT            = 0b100000000;
}
