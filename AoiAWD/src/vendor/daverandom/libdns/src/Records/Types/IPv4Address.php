<?php declare(strict_types=1);
/**
 * Represents an IPv4 address
 *
 * PHP version 5.4
 *
 * @category LibDNS
 * @package Types
 * @author Chris Wright <https://github.com/DaveRandom>
 * @copyright Copyright (c) Chris Wright <https://github.com/DaveRandom>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @version 2.0.0
 */
namespace LibDNS\Records\Types;

/**
 * Represents an IPv4 address
 *
 * @category LibDNS
 * @package Types
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class IPv4Address extends Type
{
    /**
     * @var string
     */
    protected $value = '0.0.0.0';

    /**
     * @var int[] The octets of the address
     */
    private $octets = [0, 0, 0, 0];

    /**
     * Constructor
     *
     * @param string|int[] $value String representation or octet list
     * @throws \UnexpectedValueException When the supplied value is not a valid IPv4 address
     */
    public function __construct($value = null)
    {
        if (\is_array($value)) {
            $this->setOctets($value);
        } else {
            parent::__construct($value);
        }
    }

    /**
     * Set the internal value
     *
     * @param string $value The new value
     * @throws \UnexpectedValueException When the supplied value is outside the valid length range 0 - 65535
     */
    public function setValue($value)
    {
        $this->setOctets(\explode('.', (string)$value));
    }

    /**
     * Get the address octets
     *
     * @return int[]
     */
    public function getOctets(): array
    {
        return $this->octets;
    }

    /**
     * Set the address octets
     *
     * @param int[] $octets The new address octets
     * @throws \UnexpectedValueException When the supplied octet list is not a valid IPv4 address
     */
    public function setOctets(array $octets)
    {
        if (\count($octets) !== 4) {
            throw new \UnexpectedValueException('Octet list is not a valid IPv4 address: invalid octet count');
        }

        foreach ($octets as &$octet) {
            if ((!\is_int($octet) && !\ctype_digit((string)$octet)) || $octet < 0x00 || $octet > 0xff) {
                throw new \UnexpectedValueException('Octet list is not a valid IPv4 address: invalid octet value ' . $octet);
            }

            $octet = (int) $octet;
        }

        $this->octets = \array_values($octets);
        $this->value = \implode('.', $this->octets);
    }
}
