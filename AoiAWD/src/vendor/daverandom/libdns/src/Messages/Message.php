<?php declare(strict_types=1);
/**
 * Represents a DNS protocol message
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

use LibDNS\Records\RecordCollection;
use \LibDNS\Records\RecordCollectionFactory;
use \LibDNS\Records\RecordTypes;

/**
 * Represents a DNS protocol message
 *
 * @category LibDNS
 * @package Messages
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class Message
{
    /**
     * @var int Unsigned short that identifies the DNS transaction
     */
    private $id = 0;

    /**
     * @var int Indicates the type of the message, can be indicated using the MessageTypes enum
     */
    private $type = -1;

    /**
     * @var int Message opcode, can be indicated using the MessageOpCodes enum
     */
    private $opCode = MessageOpCodes::QUERY;

    /**
     * @var bool Whether a response message is authoritative
     */
    private $authoritative = false;

    /**
     * @var bool Whether the message is truncated
     */
    private $truncated = false;

    /**
     * @var bool Whether a query desires the server to recurse the lookup
     */
    private $recursionDesired = true;

    /**
     * @var bool Whether a server could provide recursion in a response
     */
    private $recursionAvailable = false;

    /**
     * @var int Message response code, can be indicated using the MessageResponseCodes enum
     */
    private $responseCode = MessageResponseCodes::NO_ERROR;

    /**
     * @var \LibDNS\Records\RecordCollection Collection of question records
     */
    private $questionRecords;

    /**
     * @var \LibDNS\Records\RecordCollection Collection of question records
     */
    private $answerRecords;

    /**
     * @var \LibDNS\Records\RecordCollection Collection of authority records
     */
    private $authorityRecords;

    /**
     * @var \LibDNS\Records\RecordCollection Collection of authority records
     */
    private $additionalRecords;

    /**
     * Constructor
     *
     * @param \LibDNS\Records\RecordCollectionFactory $recordCollectionFactory Factory which makes RecordCollection objects
     * @param int $type Value of the message type field
     * @throws \RangeException When the supplied message type is outside the valid range 0 - 1
     */
    public function __construct(RecordCollectionFactory $recordCollectionFactory, int $type = null)
    {
        $this->questionRecords = $recordCollectionFactory->create(RecordTypes::QUESTION);
        $this->answerRecords = $recordCollectionFactory->create(RecordTypes::RESOURCE);
        $this->authorityRecords = $recordCollectionFactory->create(RecordTypes::RESOURCE);
        $this->additionalRecords = $recordCollectionFactory->create(RecordTypes::RESOURCE);

        if ($type !== null) {
            $this->setType($type);
        }
    }

    /**
     * Get the value of the message ID field
     *
     * @return int
     */
    public function getID(): int
    {
        return $this->id;
    }

    /**
     * Set the value of the message ID field
     *
     * @param int $id The new value
     * @throws \RangeException When the supplied value is outside the valid range 0 - 65535
     */
    public function setID(int $id)
    {
        if ($id < 0 || $id > 65535) {
            throw new \RangeException('Message ID must be in the range 0 - 65535');
        }

        $this->id = $id;
    }

    /**
     * Get the value of the message type field
     *
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Set the value of the message type field
     *
     * @param int $type The new value
     * @throws \RangeException When the supplied value is outside the valid range 0 - 1
     */
    public function setType(int $type)
    {
        if ($type < 0 || $type > 1) {
            throw new \RangeException('Message type must be in the range 0 - 1');
        }

        $this->type = $type;
    }

    /**
     * Get the value of the message opcode field
     *
     * @return int
     */
    public function getOpCode(): int
    {
        return $this->opCode;
    }

    /**
     * Set the value of the message opcode field
     *
     * @param int $opCode The new value
     * @throws \RangeException When the supplied value is outside the valid range 0 - 15
     */
    public function setOpCode(int $opCode)
    {
        if ($opCode < 0 || $opCode > 15) {
            throw new \RangeException('Message opcode must be in the range 0 - 15');
        }

        $this->opCode = $opCode;
    }

    /**
     * Inspect the value of the authoritative field and optionally set a new value
     *
     * @param bool $newValue The new value
     * @return bool The old value
     */
    public function isAuthoritative(bool $newValue = null): bool
    {
        $result = $this->authoritative;

        if ($newValue !== null) {
            $this->authoritative = $newValue;
        }

        return $result;
    }

    /**
     * Inspect the value of the truncated field and optionally set a new value
     *
     * @param bool $newValue The new value
     * @return bool The old value
     */
    public function isTruncated(bool $newValue = null): bool
    {
        $result = $this->truncated;

        if ($newValue !== null) {
            $this->truncated = $newValue;
        }

        return $result;
    }

    /**
     * Inspect the value of the recusion desired field and optionally set a new value
     *
     * @param bool $newValue The new value
     * @return bool The old value
     */
    public function isRecursionDesired(bool $newValue = null): bool
    {
        $result = $this->recursionDesired;

        if ($newValue !== null) {
            $this->recursionDesired = $newValue;
        }

        return $result;
    }

    /**
     * Inspect the value of the recursion available field and optionally set a new value
     *
     * @param bool $newValue The new value
     * @return bool The old value
     */
    public function isRecursionAvailable(bool $newValue = null): bool
    {
        $result = $this->recursionAvailable;

        if ($newValue !== null) {
            $this->recursionAvailable = $newValue;
        }

        return $result;
    }

    /**
     * Get the value of the message response code field
     *
     * @return int
     */
    public function getResponseCode(): int
    {
        return $this->opCode;
    }

    /**
     * Set the value of the message response code field
     *
     * @param int $responseCode The new value
     * @throws \RangeException When the supplied value is outside the valid range 0 - 15
     */
    public function setResponseCode(int $responseCode)
    {
        if ($responseCode < 0 || $responseCode > 15) {
            throw new \RangeException('Message response code must be in the range 0 - 15');
        }

        $this->responseCode = $responseCode;
    }

    /**
     * Get the question records collection
     *
     * @return \LibDNS\Records\RecordCollection
     */
    public function getQuestionRecords(): RecordCollection
    {
        return $this->questionRecords;
    }

    /**
     * Get the answer records collection
     *
     * @return \LibDNS\Records\RecordCollection
     */
    public function getAnswerRecords(): RecordCollection
    {
        return $this->answerRecords;
    }

    /**
     * Get the authority records collection
     *
     * @return \LibDNS\Records\RecordCollection
     */
    public function getAuthorityRecords(): RecordCollection
    {
        return $this->authorityRecords;
    }

    /**
     * Get the additional records collection
     *
     * @return \LibDNS\Records\RecordCollection
     */
    public function getAdditionalRecords(): RecordCollection
    {
        return $this->additionalRecords;
    }
}
