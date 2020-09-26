<?php declare(strict_types=1);
/**
 * Enumeration of possible resource QTYPE values
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

/**
 * Enumeration of possible resource QTYPE values
 *
 * @category LibDNS
 * @package Records
 * @author Chris Wright <https://github.com/DaveRandom>
 */
final class ResourceQTypes extends ResourceTypes
{
    const AXFR = 252;
    const MAILB = 253;
    const MAILA = 254;
    const ALL = 255;
}
