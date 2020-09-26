<?php declare(strict_types=1);
/**
 * Represents a DNS question record
 *
 * PHP version 5.4
 *
 * @category LibDNS
 * @package Records
 * @author Chris Wright <https://github.com/DaveRandom>
 * @copyright Copyright (c) Chris Wright <https://github.com/DaveRandom>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @version 2.0.0
 */
namespace LibDNS\Records;

use \LibDNS\Records\Types\TypeFactory;

/**
 * Represents a DNS question record
 *
 * @category LibDNS
 * @package Records
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class Question extends Record
{
    /**
     * Constructor
     *
     * @param \LibDNS\Records\Types\TypeFactory $typeFactory
     * @param int $type Resource type being requested, can be indicated using the ResourceQTypes enum
     */
    public function __construct(TypeFactory $typeFactory, int $type)
    {
        $this->typeFactory = $typeFactory;
        $this->type = $type;
    }
}
