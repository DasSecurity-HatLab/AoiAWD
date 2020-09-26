<?php declare(strict_types = 1);

namespace LibDNS;

if (\function_exists('idn_to_ascii')) {
    function normalize_name(string $label): string
    {
        if (false === $result = \idn_to_ascii($label, 0, INTL_IDNA_VARIANT_UTS46)) {
            throw new \InvalidArgumentException("Label '{$label}' could not be processed for IDN");
        }

        return $result;
    }
} else {
    function normalize_name(string $label): string
    {
        if (\preg_match('/[\x80-\xff]/', $label)) {
            throw new \InvalidArgumentException(
                "Label '{$label}' contains non-ASCII characters and IDN support is not available."
                . " Verify that ext/intl is installed for IDN support."
            );
        }

        return \strtolower($label);
    }
}
