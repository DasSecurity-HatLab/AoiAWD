<?php declare(strict_types=1);
/**
 * Creates RecordCollection objects
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

/**
 * Creates RecordCollection objects
 *
 * @category LibDNS
 * @package Records
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class RecordCollectionFactory
{
    /**
     * Create a new RecordCollection object
     *
     * @param int $type Can be indicated using the RecordTypes enum
     * @return \LibDNS\Records\RecordCollection
     * @throws \InvalidArgumentException When the specified record type is invalid
     */
    public function create(int $type): RecordCollection
    {
        return new RecordCollection($type);
    }
}
