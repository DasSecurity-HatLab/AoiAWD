<?php declare(strict_types=1);
/**
 * Creates DecodingContext objects
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

use \LibDNS\Packets\Packet;
use \LibDNS\Packets\LabelRegistry;

/**
 * Creates DecodingContext objects
 *
 * @category LibDNS
 * @package Decoder
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class DecodingContextFactory
{
    /**
     * Create a new DecodingContext object
     *
     * @param \LibDNS\Packets\Packet $packet The packet to be decoded
     * @return \LibDNS\Decoder\DecodingContext
     */
    public function create(Packet $packet): DecodingContext
    {
        return new DecodingContext($packet, new LabelRegistry);
    }
}
