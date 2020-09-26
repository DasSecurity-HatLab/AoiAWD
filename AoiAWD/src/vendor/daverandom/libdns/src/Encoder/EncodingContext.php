<?php declare(strict_types=1);
/**
 * Holds data associated with an encode operation
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
 * Holds data associated with an encode operation
 *
 * @category LibDNS
 * @package Encoder
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class EncodingContext
{
    /**
     * @var \LibDNS\Packets\Packet
     */
    private $packet;

    /**
     * @var \LibDNS\Packets\LabelRegistry
     */
    private $labelRegistry;

    /**
     * @var bool
     */
    private $compress;

    /**
     * @var bool
     */
    private $truncate = false;

    /**
     * Constructor
     *
     * @param \LibDNS\Packets\Packet $packet
     * @param \LibDNS\Packets\LabelRegistry $labelRegistry
     * @param bool $compress
     */
    public function __construct(Packet $packet, LabelRegistry $labelRegistry, bool $compress)
    {
        $this->packet = $packet;
        $this->labelRegistry = $labelRegistry;
        $this->compress = $compress;
    }

    /**
     * Get the packet
     *
     * @return \LibDNS\Packets\Packet
     */
    public function getPacket(): Packet
    {
        return $this->packet;
    }

    /**
     * Get the label registry
     *
     * @return \LibDNS\Packets\LabelRegistry
     */
    public function getLabelRegistry(): LabelRegistry
    {
        return $this->labelRegistry;
    }

    /**
     * Determine whether compression is enabled
     *
     * @return bool
     */
    public function useCompression(): bool
    {
        return $this->compress;
    }

    /**
     * Determine or set whether the message is truncated
     *
     * @param bool $truncate
     * @return bool
     */
    public function isTruncated(bool $truncate = null): bool
    {
        $result = $this->truncate;

        if ($truncate !== null) {
            $this->truncate = $truncate;
        }

        return $result;
    }
}
