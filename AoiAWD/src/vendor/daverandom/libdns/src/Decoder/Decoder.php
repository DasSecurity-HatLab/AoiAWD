<?php declare(strict_types=1);
/**
 * Decodes raw network data to Message objects
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

use LibDNS\Messages\Message;
use LibDNS\Messages\MessageFactory;
use LibDNS\Packets\Packet;
use LibDNS\Packets\PacketFactory;
use LibDNS\Records\Question;
use LibDNS\Records\QuestionFactory;
use LibDNS\Records\Resource;
use LibDNS\Records\ResourceBuilder;
use LibDNS\Records\Types\Anything;
use LibDNS\Records\Types\BitMap;
use LibDNS\Records\Types\Char;
use LibDNS\Records\Types\CharacterString;
use LibDNS\Records\Types\DomainName;
use LibDNS\Records\Types\IPv4Address;
use LibDNS\Records\Types\IPv6Address;
use LibDNS\Records\Types\Long;
use LibDNS\Records\Types\Short;
use LibDNS\Records\Types\Type;
use LibDNS\Records\Types\TypeBuilder;
use LibDNS\Records\Types\Types;

/**
 * Decodes raw network data to Message objects
 *
 * @category LibDNS
 * @package Decoder
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class Decoder
{
    /**
     * @var \LibDNS\Packets\PacketFactory
     */
    private $packetFactory;

    /**
     * @var \LibDNS\Messages\MessageFactory
     */
    private $messageFactory;

    /**
     * @var \LibDNS\Records\QuestionFactory
     */
    private $questionFactory;

    /**
     * @var \LibDNS\Records\ResourceBuilder
     */
    private $resourceBuilder;

    /**
     * @var \LibDNS\Records\Types\TypeBuilder
     */
    private $typeBuilder;

    /**
     * @var \LibDNS\Decoder\DecodingContextFactory
     */
    private $decodingContextFactory;

    /**
     * @var bool
     */
    private $allowTrailingData;

    /**
     * Constructor
     *
     * @param \LibDNS\Packets\PacketFactory $packetFactory
     * @param \LibDNS\Messages\MessageFactory $messageFactory
     * @param \LibDNS\Records\QuestionFactory $questionFactory
     * @param \LibDNS\Records\ResourceBuilder $resourceBuilder
     * @param \LibDNS\Records\Types\TypeBuilder $typeBuilder
     * @param \LibDNS\Decoder\DecodingContextFactory $decodingContextFactory
     * @param bool $allowTrailingData
     */
    public function __construct(
        PacketFactory $packetFactory,
        MessageFactory $messageFactory,
        QuestionFactory $questionFactory,
        ResourceBuilder $resourceBuilder,
        TypeBuilder $typeBuilder,
        DecodingContextFactory $decodingContextFactory,
        bool $allowTrailingData = true
    ) {
        $this->packetFactory = $packetFactory;
        $this->messageFactory = $messageFactory;
        $this->questionFactory = $questionFactory;
        $this->resourceBuilder = $resourceBuilder;
        $this->typeBuilder = $typeBuilder;
        $this->decodingContextFactory = $decodingContextFactory;
        $this->allowTrailingData = $allowTrailingData;
    }

    /**
     * Read a specified number of bytes of data from a packet
     *
     * @param \LibDNS\Packets\Packet $packet
     * @param int $length
     * @return string
     * @throws \UnexpectedValueException When the read operation does not result in the requested number of bytes
     */
    private function readDataFromPacket(Packet $packet, int $length): string
    {
        if ($packet->getBytesRemaining() < $length) {
            throw new \UnexpectedValueException('Decode error: Incomplete packet (tried to read ' . $length . ' bytes from index ' . $packet->getPointer());
        }

        return $packet->read($length);
    }

    /**
     * Decode the header section of the message
     *
     * @param \LibDNS\Decoder\DecodingContext $decodingContext
     * @param \LibDNS\Messages\Message $message
     * @throws \UnexpectedValueException When the header section is invalid
     */
    private function decodeHeader(DecodingContext $decodingContext, Message $message)
    {
        $header = \unpack('nid/nmeta/nqd/nan/nns/nar', $this->readDataFromPacket($decodingContext->getPacket(), 12));
        if (!$header) {
            throw new \UnexpectedValueException('Decode error: Header unpack failed');
        }

        $message->setID($header['id']);

        $message->setType(($header['meta'] & 0b1000000000000000) >> 15);
        $message->setOpCode(($header['meta'] & 0b0111100000000000) >> 11);
        $message->isAuthoritative((bool)(($header['meta'] & 0b0000010000000000) >> 10));
        $message->isTruncated((bool)(($header['meta'] & 0b0000001000000000) >> 9));
        $message->isRecursionDesired((bool)(($header['meta'] & 0b0000000100000000) >> 8));
        $message->isRecursionAvailable((bool)(($header['meta'] & 0b0000000010000000) >> 7));
        $message->setResponseCode($header['meta'] & 0b0000000000001111);

        $decodingContext->setExpectedQuestionRecords($header['qd']);
        $decodingContext->setExpectedAnswerRecords($header['an']);
        $decodingContext->setExpectedAuthorityRecords($header['ns']);
        $decodingContext->setExpectedAdditionalRecords($header['ar']);
    }

    /**
     * Decode an Anything field
     *
     * @param \LibDNS\Decoder\DecodingContext $decodingContext
     * @param \LibDNS\Records\Types\Anything $anything The object to populate with the result
     * @param int $length
     * @return int The number of packet bytes consumed by the operation
     * @throws \UnexpectedValueException When the packet data is invalid
     */
    private function decodeAnything(DecodingContext $decodingContext, Anything $anything, int $length): int
    {
        $anything->setValue($this->readDataFromPacket($decodingContext->getPacket(), $length));

        return $length;
    }

    /**
     * Decode a BitMap field
     *
     * @param \LibDNS\Decoder\DecodingContext $decodingContext
     * @param \LibDNS\Records\Types\BitMap $bitMap The object to populate with the result
     * @param int $length
     * @return int The number of packet bytes consumed by the operation
     * @throws \UnexpectedValueException When the packet data is invalid
     */
    private function decodeBitMap(DecodingContext $decodingContext, BitMap $bitMap, int $length): int
    {
        $bitMap->setValue($this->readDataFromPacket($decodingContext->getPacket(), $length));

        return $length;
    }

    /**
     * Decode a Char field
     *
     * @param \LibDNS\Decoder\DecodingContext $decodingContext
     * @param \LibDNS\Records\Types\Char $char The object to populate with the result
     * @return int The number of packet bytes consumed by the operation
     * @throws \UnexpectedValueException When the packet data is invalid
     */
    private function decodeChar(DecodingContext $decodingContext, Char $char): int
    {
        $value = \unpack('C', $this->readDataFromPacket($decodingContext->getPacket(), 1))[1];
        $char->setValue($value);

        return 1;
    }

    /**
     * Decode a CharacterString field
     *
     * @param \LibDNS\Decoder\DecodingContext $decodingContext
     * @param \LibDNS\Records\Types\CharacterString $characterString The object to populate with the result
     * @return int The number of packet bytes consumed by the operation
     * @throws \UnexpectedValueException When the packet data is invalid
     */
    private function decodeCharacterString(DecodingContext $decodingContext, CharacterString $characterString): int
    {
        $packet = $decodingContext->getPacket();
        $length = \ord($this->readDataFromPacket($packet, 1));
        $characterString->setValue($this->readDataFromPacket($packet, $length));

        return $length + 1;
    }

    /**
     * Decode a DomainName field
     *
     * @param \LibDNS\Decoder\DecodingContext $decodingContext
     * @param \LibDNS\Records\Types\DomainName $domainName The object to populate with the result
     * @return int The number of packet bytes consumed by the operation
     * @throws \UnexpectedValueException When the packet data is invalid
     */
    private function decodeDomainName(DecodingContext $decodingContext, DomainName $domainName): int
    {
        $packet = $decodingContext->getPacket();
        $startIndex = '0x' . \dechex($packet->getPointer());
        $labelRegistry = $decodingContext->getLabelRegistry();

        $labels = [];
        $totalLength = 0;

        while (++$totalLength && $length = \ord($this->readDataFromPacket($packet, 1))) {
            $labelType = $length & 0b11000000;

            if ($labelType === 0b00000000) {
                $index = $packet->getPointer() - 1;
                $label = $this->readDataFromPacket($packet, $length);

                \array_unshift($labels, [$index, $label]);
                $totalLength += $length;
            } else if ($labelType === 0b11000000) {
                $index = (($length & 0b00111111) << 8) | \ord($this->readDataFromPacket($packet, 1));
                $ref = $labelRegistry->lookupLabel($index);
                if ($ref === null) {
                    throw new \UnexpectedValueException('Decode error: Invalid compression pointer reference in domain name at position ' . $startIndex);
                }

                \array_unshift($labels, $ref);
                $totalLength++;

                break;
            } else {
                throw new \UnexpectedValueException('Decode error: Invalid label type ' . $labelType . 'in domain name at position ' . $startIndex);
            }
        }

        $result = [];
        foreach ($labels as $label) {
            if (\is_int($label[0])) {
                \array_unshift($result, $label[1]);
                $labelRegistry->register($result, $label[0]);
            } else {
                $result = $label;
            }
        }
        $domainName->setLabels($result);

        return $totalLength;
    }

    /**
     * Decode an IPv4Address field
     *
     * @param \LibDNS\Decoder\DecodingContext $decodingContext
     * @param \LibDNS\Records\Types\IPv4Address $ipv4Address The object to populate with the result
     * @return int The number of packet bytes consumed by the operation
     * @throws \UnexpectedValueException When the packet data is invalid
     */
    private function decodeIPv4Address(DecodingContext $decodingContext, IPv4Address $ipv4Address): int
    {
        $octets = \unpack('C4', $this->readDataFromPacket($decodingContext->getPacket(), 4));
        $ipv4Address->setOctets($octets);

        return 4;
    }

    /**
     * Decode an IPv6Address field
     *
     * @param \LibDNS\Decoder\DecodingContext $decodingContext
     * @param \LibDNS\Records\Types\IPv6Address $ipv6Address The object to populate with the result
     * @return int The number of packet bytes consumed by the operation
     * @throws \UnexpectedValueException When the packet data is invalid
     */
    private function decodeIPv6Address(DecodingContext $decodingContext, IPv6Address $ipv6Address): int
    {
        $shorts = \unpack('n8', $this->readDataFromPacket($decodingContext->getPacket(), 16));
        $ipv6Address->setShorts($shorts);

        return 16;
    }

    /**
     * Decode a Long field
     *
     * @param \LibDNS\Decoder\DecodingContext $decodingContext
     * @param \LibDNS\Records\Types\Long $long The object to populate with the result
     * @return int The number of packet bytes consumed by the operation
     * @throws \UnexpectedValueException When the packet data is invalid
     */
    private function decodeLong(DecodingContext $decodingContext, Long $long): int
    {
        $value = \unpack('N', $this->readDataFromPacket($decodingContext->getPacket(), 4))[1];
        $long->setValue($value);

        return 4;
    }

    /**
     * Decode a Short field
     *
     * @param \LibDNS\Decoder\DecodingContext $decodingContext
     * @param \LibDNS\Records\Types\Short $short The object to populate with the result
     * @return int The number of packet bytes consumed by the operation
     * @throws \UnexpectedValueException When the packet data is invalid
     */
    private function decodeShort(DecodingContext $decodingContext, Short $short): int
    {
        $value = \unpack('n', $this->readDataFromPacket($decodingContext->getPacket(), 2))[1];
        $short->setValue($value);

        return 2;
    }

    /**
     * Decode a Type field
     *
     * @param \LibDNS\Decoder\DecodingContext $decodingContext
     * @param \LibDNS\Records\Types\Type $type The object to populate with the result
     * @param int $length Expected data length
     * @return int The number of packet bytes consumed by the operation
     * @throws \UnexpectedValueException When the packet data is invalid
     * @throws \InvalidArgumentException When the Type subtype is unknown
     */
    private function decodeType(DecodingContext $decodingContext, Type $type, int $length): int
    {
        if ($type instanceof Anything) {
            $result = $this->decodeAnything($decodingContext, $type, $length);
        } else if ($type instanceof BitMap) {
            $result = $this->decodeBitMap($decodingContext, $type, $length);
        } else if ($type instanceof Char) {
            $result = $this->decodeChar($decodingContext, $type);
        } else if ($type instanceof CharacterString) {
            $result = $this->decodeCharacterString($decodingContext, $type);
        } else if ($type instanceof DomainName) {
            $result = $this->decodeDomainName($decodingContext, $type);
        } else if ($type instanceof IPv4Address) {
            $result = $this->decodeIPv4Address($decodingContext, $type);
        } else if ($type instanceof IPv6Address) {
            $result = $this->decodeIPv6Address($decodingContext, $type);
        } else if ($type instanceof Long) {
            $result = $this->decodeLong($decodingContext, $type);
        } else if ($type instanceof Short) {
            $result = $this->decodeShort($decodingContext, $type);
        } else {
            throw new \InvalidArgumentException('Unknown Type ' . \get_class($type));
        }

        return $result;
    }

    /**
     * Decode a question record
     *
     * @param \LibDNS\Decoder\DecodingContext $decodingContext
     * @return \LibDNS\Records\Question
     * @throws \UnexpectedValueException When the record is invalid
     */
    private function decodeQuestionRecord(DecodingContext $decodingContext): Question
    {
        /** @var \LibDNS\Records\Types\DomainName $domainName */
        $domainName = $this->typeBuilder->build(Types::DOMAIN_NAME);
        $this->decodeDomainName($decodingContext, $domainName);
        $meta = \unpack('ntype/nclass', $this->readDataFromPacket($decodingContext->getPacket(), 4));

        $question = $this->questionFactory->create($meta['type']);
        $question->setName($domainName);
        $question->setClass($meta['class']);

        return $question;
    }

    /**
     * Decode a resource record
     *
     * @param \LibDNS\Decoder\DecodingContext $decodingContext
     * @return \LibDNS\Records\Resource
     * @throws \UnexpectedValueException When the record is invalid
     * @throws \InvalidArgumentException When a type subtype is unknown
     */
    private function decodeResourceRecord(DecodingContext $decodingContext): Resource
    {
        /** @var \LibDNS\Records\Types\DomainName $domainName */
        $domainName = $this->typeBuilder->build(Types::DOMAIN_NAME);
        $this->decodeDomainName($decodingContext, $domainName);
        $meta = \unpack('ntype/nclass/Nttl/nlength', $this->readDataFromPacket($decodingContext->getPacket(), 10));

        $resource = $this->resourceBuilder->build($meta['type']);
        $resource->setName($domainName);
        $resource->setClass($meta['class']);
        $resource->setTTL($meta['ttl']);

        $data = $resource->getData();
        $remainingLength = $meta['length'];

        $fieldDef = $index = null;
        foreach ($resource->getData()->getTypeDefinition() as $index => $fieldDef) {
            $field = $this->typeBuilder->build($fieldDef->getType());
            $remainingLength -= $this->decodeType($decodingContext, $field, $remainingLength);
            $data->setField($index, $field);
        }

        if ($fieldDef->allowsMultiple()) {
            while ($remainingLength) {
                $field = $this->typeBuilder->build($fieldDef->getType());
                $remainingLength -= $this->decodeType($decodingContext, $field, $remainingLength);
                $data->setField(++$index, $field);
            }
        }

        if ($remainingLength !== 0) {
            throw new \UnexpectedValueException('Decode error: Invalid length for record data section');
        }

        return $resource;
    }

    /**
     * Decode a Message from raw network data
     *
     * @param string $data The data string to decode
     * @return \LibDNS\Messages\Message
     * @throws \UnexpectedValueException When the packet data is invalid
     * @throws \InvalidArgumentException When a type subtype is unknown
     */
    public function decode(string $data): Message
    {
        $packet = $this->packetFactory->create($data);
        $decodingContext = $this->decodingContextFactory->create($packet);
        $message = $this->messageFactory->create();

        $this->decodeHeader($decodingContext, $message);

        $questionRecords = $message->getQuestionRecords();
        $expected = $decodingContext->getExpectedQuestionRecords();
        for ($i = 0; $i < $expected; $i++) {
            $questionRecords->add($this->decodeQuestionRecord($decodingContext));
        }

        $answerRecords = $message->getAnswerRecords();
        $expected = $decodingContext->getExpectedAnswerRecords();
        for ($i = 0; $i < $expected; $i++) {
            $answerRecords->add($this->decodeResourceRecord($decodingContext));
        }

        $authorityRecords = $message->getAuthorityRecords();
        $expected = $decodingContext->getExpectedAuthorityRecords();
        for ($i = 0; $i < $expected; $i++) {
            $authorityRecords->add($this->decodeResourceRecord($decodingContext));
        }

        $additionalRecords = $message->getAdditionalRecords();
        $expected = $decodingContext->getExpectedAdditionalRecords();
        for ($i = 0; $i < $expected; $i++) {
            $additionalRecords->add($this->decodeResourceRecord($decodingContext));
        }

        if (!$this->allowTrailingData && $packet->getBytesRemaining() !== 0) {
            throw new \UnexpectedValueException('Decode error: Unexpected data at end of packet');
        }

        return $message;
    }
}
