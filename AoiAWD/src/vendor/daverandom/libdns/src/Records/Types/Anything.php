<?php declare(strict_types=1);
/**
 * Represents a generic binary data string
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
 * Represents a generic binary data string
 *
 * @category LibDNS
 * @package Types
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class Anything extends Type
{
    /**
     * @var string
     */
    protected $value = '';

    /**
     * Set the internal value
     *
     * @param string $value The new value
     * @throws \UnexpectedValueException When the supplied value is outside the valid length range 0 - 65535
     */
    public function setValue($value)
    {
        $value = (string)$value;

        if (\strlen($value) > 65535) {
            throw new \UnexpectedValueException('Untyped string length must be in the range 0 - 65535');
        }

        $this->value = $value;
    }
}
