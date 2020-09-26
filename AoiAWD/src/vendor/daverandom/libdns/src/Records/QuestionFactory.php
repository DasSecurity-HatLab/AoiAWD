<?php declare(strict_types=1);
/**
 * Creates Question objects
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
 * Creates Question objects
 *
 * @category LibDNS
 * @package Records
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class QuestionFactory
{
    /**
     * Create a new Question object
     *
     * @param int $type The resource type
     * @return \LibDNS\Records\Question
     */
    public function create(int $type): Question
    {
        return new Question(new TypeFactory, $type);
    }
}
