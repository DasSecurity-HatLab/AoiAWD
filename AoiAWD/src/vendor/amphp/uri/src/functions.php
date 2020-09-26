<?php

namespace Amp\Uri;

/**
 * Checks whether a string is a valid DNS name.
 *
 * @param string $name String to check.
 *
 * @return bool
 */
function isValidDnsName(string $name) {
    try {
        normalizeDnsName($name);
        return true;
    } catch (InvalidDnsNameException $e) {
        return false;
    }
}

/**
 * Normalizes a DNS name and automatically checks it for validity.
 *
 * @param string $name DNS name.
 *
 * @return string Normalized DNS name.
 *
 * @throws InvalidDnsNameException If an invalid name or an IDN name without ext/intl being installed has been passed.
 */
function normalizeDnsName(string $name): string {
    static $pattern = '/^(?<name>[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)(\.(?&name))*$/i';

    if (\function_exists('idn_to_ascii') && \defined('INTL_IDNA_VARIANT_UTS46')) {
        if (false === $result = \idn_to_ascii($name, 0, \INTL_IDNA_VARIANT_UTS46)) {
            throw new InvalidDnsNameException("Name '{$name}' could not be processed for IDN.");
        }

        $name = $result;
    } else {
        if (\preg_match('/[\x80-\xff]/', $name)) {
            throw new InvalidDnsNameException(
                "Name '{$name}' contains non-ASCII characters and IDN support is not available. " .
                "Verify that ext/intl is installed for IDN support and that ICU is at least version 4.6."
            );
        }
    }

    if (isset($name[253]) || !\preg_match($pattern, $name)) {
        throw new InvalidDnsNameException("Name '{$name}' is not a valid hostname.");
    }

    return $name;
}
