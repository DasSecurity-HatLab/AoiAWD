<?php declare(strict_types=1);
/**
 * Creates Packet objects
 *
 * PHP version 5.4
 *
 * @category LibDNS
 * @package Packets
 * @author Chris Wright <https://github.com/DaveRandom>
 * @copyright Copyright (c) Chris Wright <https://github.com/DaveRandom>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @version 2.0.0
 */
namespace LibDNS\Packets;

/**
 * Creates Packet objects
 *
 * @category LibDNS
 * @package Packets
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class PacketFactory
{
    /**
     * Create a new Packet object
     *
     * @param string $data
     * @return \LibDNS\Packets\Packet
     */
    public function create(string $data = ''): Packet
    {
        return new Packet($data);
    }
}
