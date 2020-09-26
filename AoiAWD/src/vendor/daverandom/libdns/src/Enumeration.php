<?php declare(strict_types=1);
/**
 * Base class for enumerations to prevent instantiation
 *
 * PHP version 5.4
 *
 * @category LibDNS
 * @package LibDNS
 * @author Chris Wright <https://github.com/DaveRandom>
 * @copyright Copyright (c) Chris Wright <https://github.com/DaveRandom>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @version 2.0.0
 */
namespace LibDNS;

/**
 * Base class for enumerations to prevent instantiation
 *
 * @category LibDNS
 * @package LibDNS
 * @author Chris Wright <https://github.com/DaveRandom>
 */
abstract class Enumeration
{
    final protected function __construct()
    {
        throw new \LogicException('Enumerations cannot be instantiated');
    }
}
