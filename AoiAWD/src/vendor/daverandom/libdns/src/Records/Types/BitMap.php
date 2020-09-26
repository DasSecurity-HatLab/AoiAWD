<?php declare(strict_types=1);
/**
 * Represents a bit map
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
 * Represents a bit map
 *
 * @category LibDNS
 * @package Types
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class BitMap extends Type
{
    /**
     * @var string
     */
    protected $value = '';

    /**
     * Set the internal value
     *
     * @param string $value The new value
     */
    public function setValue($value)
    {
        $this->value = (string)$value;
    }

    /**
     * Inspect the value of the bit at the specific index and optionally set a new value
     *
     * @param int $index
     * @param bool $newValue The new value
     * @return bool The old value
     */
    public function isBitSet(int $index, bool $newValue = null): bool
    {
        $charIndex = (int)($index / 8);
        $bitMask = 0b10000000 >> ($index % 8);

        $result = false;
        if (isset($this->value[$charIndex])) {
            $result = (bool) (\ord($this->value[$charIndex]) & $bitMask);
        }

        if (isset($newValue) && $newValue != $result) {
            if (!isset($this->value[$charIndex])) {
                $this->value = \str_pad($this->value, $charIndex + 1, "\x00", STR_PAD_RIGHT);
            }

            $this->value[$charIndex] = \chr((\ord($this->value[$charIndex]) & ~$bitMask) | ($newValue ? $bitMask : 0));
        }

        return $result;
    }
}
