<?php declare(strict_types=1);
/**
 * Represents an 8-bit unsigned integer
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
 * Represents an 8-bit unsigned integer
 *
 * @category LibDNS
 * @package Types
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class Char extends Type
{
    /**
     * @var int
     */
    protected $value = 0;

    /**
     * Set the internal value
     *
     * @param string $value The new value
     * @throws \UnderflowException When the supplied value is less than 0
     * @throws \OverflowException When the supplied value is greater than 255
     */
    public function setValue($value)
    {
        $value = (int) $value;

        if ($value < 0) {
            throw new \UnderflowException('Char value must be in the range 0 - 255');
        } else if ($value > 255) {
            throw new \OverflowException('Char value must be in the range 0 - 255');
        }

        $this->value = $value;
    }
}
