<?php declare(strict_types=1);
/**
 * Factory which creates Message objects
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

use \LibDNS\Records\RecordCollectionFactory;

/**
 * Factory which creates Message objects
 *
 * @category LibDNS
 * @package Messages
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class MessageFactory
{
    /**
     * Create a new Message object
     *
     * @param int $type Value of the message type field
     * @return \LibDNS\Messages\Message
     */
    public function create(int $type = null): Message
    {
        return new Message(new RecordCollectionFactory, $type);
    }
}
