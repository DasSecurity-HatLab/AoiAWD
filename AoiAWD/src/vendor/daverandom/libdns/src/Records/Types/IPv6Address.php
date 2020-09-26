<?php declare(strict_types=1);
/**
 * Represents an IPv6 address
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
 * Represents an IPv6 address
 *
 * @category LibDNS
 * @package Types
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class IPv6Address extends Type
{
    /**
     * @var string
     */
    protected $value = '::';

    /**
     * @var int[] The shorts of the address
     */
    private $shorts = [0, 0, 0, 0, 0, 0, 0, 0];

    /**
     * Create a compressed string representation of an IPv6 address
     *
     * @param int[] $shorts Address shorts
     * @return string
     */
    private function createCompressedString($shorts)
    {
        $compressLen = $compressPos = $currentLen = $currentPos = 0;
        $inBlock = false;

        for ($i = 0; $i < 8; $i++) {
            if ($shorts[$i] === 0) {
                if (!$inBlock) {
                    $inBlock = true;
                    $currentPos = $i;
                }

                $currentLen++;
            } else if ($inBlock) {
                if ($currentLen > $compressLen) {
                    $compressLen = $currentLen;
                    $compressPos = $currentPos;
                }

                $inBlock = false;
                $currentPos = $currentLen = 0;
            }

            $shorts[$i] = \dechex($shorts[$i]);
        }
        if ($inBlock) {
            $compressLen = $currentLen;
            $compressPos = $currentPos;
        }

        if ($compressLen > 1) {
            if ($compressLen === 8) {
                $replace = ['', '', ''];
            } else if ($compressPos === 0 || $compressPos + $compressLen === 8) {
                $replace = ['', ''];
            } else {
                $replace = [''];
            }

            \array_splice($shorts, $compressPos, $compressLen, $replace);
        }

        return \implode(':', $shorts);
    }

    /**
     * Constructor
     *
     * @param string|int[] $value String representation or shorts list
     * @throws \UnexpectedValueException When the supplied value is not a valid IPv6 address
     */
    public function __construct($value = null)
    {
        if (\is_array($value)) {
            $this->setShorts($value);
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
        $shorts = \explode(':', (string)$value);

        $count = \count($shorts);
        if ($count < 3 || $count > 8) {
            throw new \UnexpectedValueException('Value is not a valid IPv6 address: invalid short count');
        } else if ($shorts[0] === '' && $shorts[1] === '') {
            $shorts = \array_pad($shorts, -8, '0');
        } else if ($shorts[$count - 2] === '' && $shorts[$count - 1] === '') {
            $shorts = \array_pad($shorts, 8, '0');
        } else if (false !== $pos = \array_search('', $shorts, true)) {
            \array_splice($shorts, $pos, 1, \array_fill(0, 8 - ($count - 1), '0'));
        }

        $this->setShorts(\array_map('hexdec', $shorts));
    }

    /**
     * Get the address shorts
     *
     * @return int[]
     */
    public function getShorts(): array
    {
        return $this->shorts;
    }

    /**
     * Set the address shorts
     *
     * @param int[] $shorts The new address shorts
     * @throws \UnexpectedValueException When the supplied short list is not a valid IPv6 address
     */
    public function setShorts(array $shorts)
    {
        if (\count($shorts) !== 8) {
            throw new \UnexpectedValueException('Short list is not a valid IPv6 address: invalid short count');
        }

        foreach ($shorts as &$short) {
            if ((!\is_int($short) && !\ctype_digit((string)$short)) || $short < 0x0000 || $short > 0xffff) {
                throw new \UnexpectedValueException('Short list is not a valid IPv6 address: invalid short value ' . $short);
            }

            $short = (int) $short;
        }

        $this->shorts = \array_values($shorts);
        $this->value = $this->createCompressedString($this->shorts);
    }
}
