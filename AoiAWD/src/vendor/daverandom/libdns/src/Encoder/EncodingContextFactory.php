<?php declare(strict_types=1);
/**
 * Creates EncodingContext objects
 *
 * PHP version 5.4
 *
 * @category LibDNS
 * @package Encoder
 * @author Chris Wright <https://github.com/DaveRandom>
 * @copyright Copyright (c) Chris Wright <https://github.com/DaveRandom>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @version 2.0.0
 */
namespace LibDNS\Encoder;

use \LibDNS\Packets\Packet;
use \LibDNS\Packets\LabelRegistry;

/**
 * Creates EncodingContext objects
 *
 * @category LibDNS
 * @package Encoder
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class EncodingContextFactory
{
    /**
     * Create a new EncodingContext object
     *
     * @param \LibDNS\Packets\Packet $packet   The packet to be decoded
     * @param bool $compress Whether message compression is enabled
     * @return \LibDNS\Encoder\EncodingContext
     */
    public function create(Packet $packet, bool $compress): EncodingContext
    {
        return new EncodingContext($packet, new LabelRegistry, $compress);
    }
}
