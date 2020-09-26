<?php declare(strict_types=1);
/**
 * Enumeration of possible message response codes
 *
 * PHP version 5.4
 *
 * @category LibDNS
 * @package Messages
 * @author Chris Wright <https://github.com/DaveRandom>
 * @copyright Copyright (c) Chris Wright <https://github.com/DaveRandom>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @version 2.0.0
 */
namespace LibDNS\Messages;

use \LibDNS\Enumeration;

/**
 * Enumeration of possible message types
 *
 * @category LibDNS
 * @package Messages
 * @author Chris Wright <https://github.com/DaveRandom>
 */
final class MessageResponseCodes extends Enumeration
{
    const NO_ERROR = 0;
    const FORMAT_ERROR = 1;
    const SERVER_FAILURE = 2;
    const NAME_ERROR = 3;
    const NOT_IMPLEMENTED = 4;
    const REFUSED = 5;
}
