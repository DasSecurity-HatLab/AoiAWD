<?php

namespace Amp\Log;

function hasColorSupport(): bool {
    $os = (\stripos(\PHP_OS, "WIN") === 0) ? "win" : \strtolower(\PHP_OS);

    // @see https://github.com/symfony/symfony/blob/v4.0.6/src/Symfony/Component/Console/Output/StreamOutput.php#L91
    // @license https://github.com/symfony/symfony/blob/v4.0.6/LICENSE
    if ($os === 'win') {
        $windowsVersion = PHP_WINDOWS_VERSION_MAJOR . '.' . PHP_WINDOWS_VERSION_MINOR . '.' . PHP_WINDOWS_VERSION_BUILD;

        return \function_exists('sapi_windows_vt100_support') && @\sapi_windows_vt100_support(\STDOUT)
            || $windowsVersion === '10.0.10586' // equals is correct here, newer versions use the above function
            || false !== \getenv('ANSICON')
            || 'ON' === \getenv('ConEmuANSI')
            || 'xterm' === \getenv('TERM');
    }

    if (\function_exists('posix_isatty')) {
        return @\posix_isatty(\STDOUT);
    }

    return false;
}
