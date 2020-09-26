<?php declare(strict_types=1);
/**
 * Holds data associated with a decode operation
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
 * Holds data associated with a decode operation
 *
 * @category LibDNS
 * @package Decoder
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class DecodingContext
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
     * @var int
     */
    private $expectedQuestionRecords = 0;

    /**
     * @var int
     */
    private $expectedAnswerRecords = 0;

    /**
     * @var int
     */
    private $expectedAuthorityRecords = 0;

    /**
     * @var int
     */
    private $expectedAdditionalRecords = 0;

    /**
     * Constructor
     *
     * @param \LibDNS\Packets\Packet $packet
     * @param \LibDNS\Packets\LabelRegistry $labelRegistry
     */
    public function __construct(Packet $packet, LabelRegistry $labelRegistry)
    {
        $this->packet = $packet;
        $this->labelRegistry = $labelRegistry;
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
     * Get the number of question records expected in the message
     *
     * @return int
     */
    public function getExpectedQuestionRecords(): int
    {
        return $this->expectedQuestionRecords;
    }

    /**
     * Get the number of question records expected in the message
     *
     * @param int $expectedQuestionRecords
     */
    public function setExpectedQuestionRecords(int $expectedQuestionRecords)
    {
        $this->expectedQuestionRecords = $expectedQuestionRecords;
    }

    /**
     * Get the number of answer records expected in the message
     *
     * @return int
     */
    public function getExpectedAnswerRecords(): int
    {
        return $this->expectedAnswerRecords;
    }

    /**
     * Set the number of answer records expected in the message
     *
     * @param int $expectedAnswerRecords
     */
    public function setExpectedAnswerRecords(int $expectedAnswerRecords)
    {
        $this->expectedAnswerRecords = $expectedAnswerRecords;
    }

    /**
     * Get the number of authority records expected in the message
     *
     * @return int
     */
    public function getExpectedAuthorityRecords(): int
    {
        return $this->expectedAuthorityRecords;
    }

    /**
     * Set the number of authority records expected in the message
     *
     * @param int $expectedAuthorityRecords
     */
    public function setExpectedAuthorityRecords(int $expectedAuthorityRecords)
    {
        $this->expectedAuthorityRecords = $expectedAuthorityRecords;
    }

    /**
     * Get the number of additional records expected in the message
     *
     * @return int
     */
    public function getExpectedAdditionalRecords(): int
    {
        return $this->expectedAdditionalRecords;
    }

    /**
     * Set the number of additional records expected in the message
     *
     * @param int $expectedAdditionalRecords
     */
    public function setExpectedAdditionalRecords(int $expectedAdditionalRecords)
    {
        $this->expectedAdditionalRecords = $expectedAdditionalRecords;
    }
}
