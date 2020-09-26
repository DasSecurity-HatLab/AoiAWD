<?php declare(strict_types=1);
/**
 * Represents a fully qualified domain name
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
 * Represents a fully qualified domain name
 *
 * @category LibDNS
 * @package Types
 * @author Chris Wright <https://github.com/DaveRandom>
 */
class DomainName extends Type
{
    const FLAG_NO_COMPRESSION = 0x80000000;

    /**
     * @var string
     */
    protected $value = '';

    /**
     * @var string[] The value as a list of labels
     */
    private $labels = [];

    /**
     * Constructor
     *
     * @param string|string[] $value
     * @throws \UnexpectedValueException When the supplied value is not a valid domain name
     */
    public function __construct($value = null)
    {
        if (\is_array($value)) {
            $this->setLabels($value);
        } else {
            parent::__construct($value);
        }
    }

    /**
     * Set the internal value
     *
     * @param string $value The new value
     * @throws \UnexpectedValueException When the supplied value is not a valid domain name
     */
    public function setValue($value)
    {
        $this->setLabels(\explode('.', (string)$value));
    }

    /**
     * Get the domain name labels
     *
     * @param bool $tldFirst Whether to return the label list ordered with the TLD label first
     * @return string[]
     */
    public function getLabels($tldFirst = false): array
    {
        return $tldFirst ? \array_reverse($this->labels) : $this->labels;
    }

    /**
     * Set the domain name labels
     *
     * @param string[] $labels   The new label list
     * @param bool $tldFirst Whether the supplied label list is ordered with the TLD label first
     * @throws \UnexpectedValueException When the supplied label list is not a valid domain name
     */
    public function setLabels(array $labels, $tldFirst = false)
    {
        if (!$labels) {
            $this->labels = [];
            $this->value = '';
            return;
        }

        $length = $count = 0;

        foreach ($labels as &$label) {
            $label = \LibDNS\normalize_name($label);
            $labelLength = \strlen($label);
            if ($labelLength > 63) {
                throw new \InvalidArgumentException('Label list is not a valid domain name: Label ' . $label . ' length exceeds 63 byte limit');
            }
            $length += $labelLength + 1;
            $count++;
        }

        $tld = $tldFirst ? $labels[0] : $labels[$count - 1];
        if ($tld === '') {
            $length--;
        }

        if ($length + 1 > 255) {
            throw new \InvalidArgumentException('Label list is not a valid domain name: Total length exceeds 255 byte limit');
        }

        $this->labels = $tldFirst ? \array_reverse($labels) : $labels;
        $this->value = \implode('.', $this->labels);
    }
}
