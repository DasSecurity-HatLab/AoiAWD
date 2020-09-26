<?php declare(strict_types=1);
/**
 * Maintains a list of the relationships between domain name labels and the first point at
 * which they appear in a packet
 *
 * PHP version 5.4
 *
 * @category LibDNS
 * @package Packets
 * @author Chris Wright <https://github.com/DaveRandom>
 * @copyright Copyright (c) Chris Wright <https://github.com/DaveRandom>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @version 2.0.0
 */
namespace LibDNS\Packets;

/**
 * Creates Packet objects
 *
 * @category LibDNS
 * @package Packets
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class LabelRegistry
{
    /**
     * @var int[] Map of labels to indexes
     */
    private $labels = [];

    /**
     * @var string[][] Map of indexes to labels
     */
    private $indexes = [];

    /**
     * Register a new relationship
     *
     * @param string|string[] $labels
     * @param int $index
     */
    public function register($labels, int $index)
    {
        if (\is_array($labels)) {
            $labelsArr = $labels;
            $labelsStr = \implode('.', $labels);
        } else {
            $labelsArr = \explode('.', $labels);
            $labelsStr = (string) $labels;
        }

        if (!isset($this->labels[$labelsStr]) || $index < $this->labels[$labelsStr]) {
            $this->labels[$labelsStr] = $index;
        }

        $this->indexes[$index] = $labelsArr;
    }

    /**
     * Lookup the index of a label
     *
     * @param string $label
     * @return int|null
     */
    public function lookupIndex(string $label)
    {
        return $this->labels[$label] ?? null;
    }

    /**
     * Lookup the label at an index
     *
     * @param int $index
     * @return string[]|null
     */
    public function lookupLabel(int $index)
    {
        return $this->indexes[$index] ?? null;
    }
}
