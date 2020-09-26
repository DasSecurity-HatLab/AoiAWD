<?php

namespace Amp\Socket\Internal;

use Amp\Deferred;
use Amp\Failure;
use Amp\Loop;
use Amp\Promise;
use Amp\Socket\CryptoException;
use Amp\Success;
use League\Uri;
use function Amp\call;

/**
 * Parse an URI into [scheme, host, port].
 *
 * @param string $uri
 *
 * @return array
 *
 * @throws \Error If an invalid URI has been passed.
 *
 * @internal
 */
function parseUri(string $uri): array
{
    if (\stripos($uri, "unix://") === 0 || \stripos($uri, "udg://") === 0) {
        list($scheme, $path) = \explode("://", $uri, 2);
        return [$scheme, \ltrim($path, "/"), 0];
    }

    if (\strpos($uri, "://") === false) {
        // Set a default scheme of tcp if none was given.
        $uri = "tcp://" . $uri;
    }

    try {
        $uriParts = Uri\parse($uri);
    } catch (\Exception $exception) {
        throw new \Error("Invalid URI: {$uri}", 0, $exception);
    }

    $scheme = $uriParts["scheme"];
    $host = $uriParts["host"] ?? "";
    $port = $uriParts["port"] ?? 0;

    if (!($scheme === "tcp" || $scheme === "udp")) {
        throw new \Error(
            "Invalid URI scheme ({$scheme}); tcp, udp, unix or udg scheme expected"
        );
    }

    if ($host === "" || $port === 0) {
        throw new \Error(
            "Invalid URI: {$uri}; host and port components required"
        );
    }

    if (\strpos($host, ":") !== false) { // IPv6 address
        $host = \sprintf("[%s]", \trim($host, "[]"));
    }

    return [$scheme, $host, $port];
}

/**
 * Enable encryption on an existing socket stream.
 *
 * @param resource $socket
 * @param array    $options
 * @param bool     $force Forces enabling without prior disabling if already enabled.
 *
 * @return Promise
 *
 * @throws \Error If an invalid options array has been passed.
 *
 * @internal
 */
function enableCrypto($socket, array $options = [], bool $force = false): Promise
{
    $ctx = \stream_context_get_options($socket);

    if (!$force && !empty($ctx['ssl']) && !empty($ctx["ssl"]["_enabled"])) {
        $cmp = \array_merge($ctx["ssl"], $options["ssl"] ?? []);
        $ctx = $ctx['ssl'];

        // Use weak comparison so the order of the items doesn't matter
        if ($ctx == $cmp) {
            return new Success;
        }

        return call(function () use ($socket, $options) {
            yield disableCrypto($socket);
            return enableCrypto($socket, $options);
        });
    }

    $options["ssl"]["_enabled"] = true; // avoid recursion

    \error_clear_last();

    \stream_context_set_option($socket, $options);
    $result = @\stream_socket_enable_crypto($socket, $enable = true);

    // Yes, that function can return true / false / 0, don't use weak comparisons.
    if ($result === true) {
        return new Success($socket);
    }

    if ($result === false) {
        return new Failure(new CryptoException(
            "Crypto negotiation failed: " . (\error_get_last()["message"] ?? "Unknown error")
        ));
    }

    return call(function () use ($socket) {
        $deferred = new Deferred;

        $watcher = Loop::onReadable($socket, function (string $watcher, $socket, Deferred $deferred) {
            $result = @\stream_socket_enable_crypto($socket, $enable = true);

            // If $result is 0, just wait for the next invocation
            if ($result === true) {
                $deferred->resolve();
            } elseif ($result === false) {
                $deferred->fail(new CryptoException("Crypto negotiation failed: " . (\feof($socket)
                        ? "Connection reset by peer"
                        : \error_get_last()["message"])));
            }
        }, $deferred);

        try {
            yield $deferred->promise();
        } finally {
            Loop::cancel($watcher);
        }

        return $socket;
    });
}

/**
 * Disable encryption on an existing socket stream.
 *
 * @param resource $socket
 *
 * @return Promise
 *
 * @internal
 */
function disableCrypto($socket): Promise
{
    // note that disabling crypto *ALWAYS* returns false, immediately
    \stream_context_set_option($socket, ["ssl" => ["_enabled" => false]]);
    @\stream_socket_enable_crypto($socket, false);

    return new Success;
}

/**
 * Normalizes "bindto" options to add a ":0" in case no port is present, otherwise PHP will silently ignore those.
 *
 * @param string|null $bindTo
 *
 * @return string|null
 *
 * @throws \Error If an invalid option has been passed.
 */
function normalizeBindToOption(string $bindTo = null)
{
    if ($bindTo === null) {
        // all fine
        return null;
    } elseif (\preg_match("/\\[(?P<ip>[0-9a-f:]+)\\](:(?P<port>\\d+))?$/", $bindTo ?? "", $match)) {
        $ip = $match['ip'];
        $port = $match['port'] ?? 0;

        if (@\inet_pton($ip) === false) {
            throw new \Error("Invalid IPv6 address: {$ip}");
        }

        if ($port < 0 || $port > 65535) {
            throw new \Error("Invalid port: {$port}");
        }

        return "[{$ip}]:{$port}";
    }

    if (\preg_match("/(?P<ip>\\d+\\.\\d+\\.\\d+\\.\\d+)(:(?P<port>\\d+))?$/", $bindTo ?? "", $match)) {
        $ip = $match['ip'];
        $port = $match['port'] ?? 0;

        if (@\inet_pton($ip) === false) {
            throw new \Error("Invalid IPv4 address: {$ip}");
        }

        if ($port < 0 || $port > 65535) {
            throw new \Error("Invalid port: {$port}");
        }

        return "{$ip}:{$port}";
    }

    throw new \Error("Invalid bindTo value: {$bindTo}");
}

/**
 * Cleans up return values of stream_socket_get_name.
 *
 * @param string|false $address
 *
 * @return string|null
 */
function cleanupSocketName($address)
{
    // https://3v4l.org/5C1lo
    if ($address === false || $address === "\0") {
        return null;
    }

    // Check if this is an IPv6 address which includes multiple colons but no square brackets
    // @see https://github.com/reactphp/socket/blob/v0.8.10/src/TcpServer.php#L179-L184
    // @license https://github.com/reactphp/socket/blob/v0.8.10/LICENSE
    $pos = \strrpos($address, ':');
    if ($pos !== false && \strpos($address, ':') < $pos && $address[0] !== '[') {
        $port = \substr($address, $pos + 1);
        $address = '[' . \substr($address, 0, $pos) . ']:' . $port;
    }
    // -- End of imported code ----- //

    return $address;
}
