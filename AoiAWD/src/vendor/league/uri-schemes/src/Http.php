<?php

/**
 * League.Uri (http://uri.thephpleague.com).
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license    https://github.com/thephpleague/uri-schemes/blob/master/LICENSE (MIT License)
 * @version    1.2.1
 * @link       https://github.com/thephpleague/uri-schemes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Uri;

use Psr\Http\Message\UriInterface as Psr7UriInterface;

use function base64_decode;
use function explode;
use function filter_var;
use function preg_match;
use function rawurlencode;
use function strpos;
use function strtolower;
use function substr;
use const FILTER_FLAG_IPV4;
use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;
use const FILTER_VALIDATE_IP;

/**
 * Immutable Value object representing a HTTP(s) Uri.
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since      1.2.0
 */
class Http extends AbstractUri implements Psr7UriInterface
{
    /**
     * @inheritdoc
     */
    protected static $supported_schemes = [
        'http' => 80,
        'https' => 443,
    ];

    /**
     * Tell whether the Http(s) URI is in valid state.
     *
     * A valid HTTP(S) URI:
     *
     * <ul>
     * <li>can be schemeless or supports only 'http' and 'https' schemes
     * <li>Host can not be an empty string
     * <li>If a scheme is defined an authority must be present
     * </ul>
     *
     * @see https://tools.ietf.org/html/rfc6455#section-3
     */
    protected function isValidUri(): bool
    {
        return '' !== $this->host
            && (null === $this->scheme || isset(static::$supported_schemes[$this->scheme]))
            && !('' != $this->scheme && null === $this->host);
    }

    /**
     * @inheritdoc
     */
    protected static function filterPort($port)
    {
        if (null === $port) {
            return $port;
        }

        if (1 > $port || 65535 < $port) {
            throw UriException::createFromInvalidPort($port);
        }

        return $port;
    }

    /**
     * Create a new instance from the environment.
     *
     * @param array $server the server and execution environment information array typically ($_SERVER)
     *
     * @return static
     */
    public static function createFromServer(array $server): self
    {
        list($user, $pass) = static::fetchUserInfo($server);
        list($host, $port) = static::fetchHostname($server);
        list($path, $query) = static::fetchRequestUri($server);

        return new static(static::fetchScheme($server), $user, $pass, $host, $port, $path, $query);
    }

    /**
     * Returns the environment scheme.
     *
     * @param array $server the environment server typically $_SERVER
     */
    protected static function fetchScheme(array $server): string
    {
        $server += ['HTTPS' => ''];
        $res = filter_var($server['HTTPS'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $res !== false ? 'https' : 'http';
    }

    /**
     * Returns the environment user info.
     *
     * @param array $server the environment server typically $_SERVER
     */
    protected static function fetchUserInfo(array $server): array
    {
        $server += ['PHP_AUTH_USER' => null, 'PHP_AUTH_PW' => null, 'HTTP_AUTHORIZATION' => ''];
        $user = $server['PHP_AUTH_USER'];
        $pass = $server['PHP_AUTH_PW'];
        if (0 === strpos(strtolower($server['HTTP_AUTHORIZATION']), 'basic')) {
            list($user, $pass) = explode(':', base64_decode(substr($server['HTTP_AUTHORIZATION'], 6)), 2) + [1 => null];
        }

        if (null !== $user) {
            $user = rawurlencode($user);
        }

        if (null !== $pass) {
            $pass = rawurlencode($pass);
        }

        return [$user, $pass];
    }

    /**
     * Returns the environment host.
     *
     * @param array $server the environment server typically $_SERVER
     *
     * @throws UriException If the host can not be detected
     */
    protected static function fetchHostname(array $server): array
    {
        $server += ['SERVER_PORT' => null];
        if (null !== $server['SERVER_PORT']) {
            $server['SERVER_PORT'] = (int) $server['SERVER_PORT'];
        }

        if (isset($server['HTTP_HOST'])) {
            preg_match(',^(?<host>(\[.*\]|[^:])*)(\:(?<port>[^/?\#]*))?$,x', $server['HTTP_HOST'], $matches);

            return [
                $matches['host'],
                isset($matches['port']) ? (int) $matches['port'] : $server['SERVER_PORT'],
            ];
        }

        if (!isset($server['SERVER_ADDR'])) {
            throw new UriException('Hostname could not be detected');
        }

        if (!filter_var($server['SERVER_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $server['SERVER_ADDR'] = '['.$server['SERVER_ADDR'].']';
        }

        return [$server['SERVER_ADDR'], $server['SERVER_PORT']];
    }

    /**
     * Returns the environment path.
     *
     * @param array $server the environment server typically $_SERVER
     */
    protected static function fetchRequestUri(array $server): array
    {
        $server += ['IIS_WasUrlRewritten' => null, 'UNENCODED_URL' => '', 'PHP_SELF' => '', 'QUERY_STRING' => null];
        if ('1' === $server['IIS_WasUrlRewritten'] && '' !== $server['UNENCODED_URL']) {
            return explode('?', $server['UNENCODED_URL'], 2) + [1 => null];
        }

        if (isset($server['REQUEST_URI'])) {
            list($path, ) = explode('?', $server['REQUEST_URI'], 2);
            $query = ('' !== $server['QUERY_STRING']) ? $server['QUERY_STRING'] : null;

            return [$path, $query];
        }

        return [$server['PHP_SELF'], $server['QUERY_STRING']];
    }
}
