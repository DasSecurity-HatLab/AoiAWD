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

use function array_map;
use function explode;
use function implode;
use function in_array;
use function preg_match;
use function strlen;
use function substr;

/**
 * Immutable Value object representing a File Uri.
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since      1.2.0
 */
class File extends AbstractUri
{
    /**
     * @inheritdoc
     */
    protected static $supported_schemes = [
        'file' => null,
    ];

    /**
     * Tell whether the File URI is in valid state.
     *
     * A valid Data URI:
     *
     * <ul>
     * <li>can not contain a userinfo component
     * <li>can not contain a port component
     * <li>can not contain a query component
     * <li>can not contain a fragment component
     * <li>only support the 'file' scheme or no scheme
     * <li>if the scheme is present, the host must be defined
     * </ul>
     *
     * @see https://tools.ietf.org/html/rfc2397#section-3
     */
    protected function isValidUri(): bool
    {
        return null === $this->user_info
            && null === $this->port
            && null === $this->query
            && null === $this->fragment
            && in_array($this->scheme, [null, 'file'], true)
            && !('' != $this->scheme && null === $this->host);
    }

    /**
     * Format the Host component.
     *
     * @see https://tools.ietf.org/html/rfc1738#section-3.10
     *
     *  As a special case, <host> can be the string "localhost" or the empty
     *  string; this is interpreted as `the machine from which the URL is
     *  being interpreted'.
     *
     * @param string|null $host
     *
     * @return string|null
     */
    protected function formatHost($host)
    {
        if ('' === $host) {
            $host = 'localhost';
        }

        return parent::formatHost($host);
    }

    /**
     * Create a new instance from a Unix path string.
     *
     * @return static
     */
    public static function createFromUnixPath(string $uri = ''): self
    {
        $uri = implode('/', array_map('rawurlencode', explode('/', $uri)));
        if ('/' === ($uri[0] ?? '')) {
            return new static('file', null, null, 'localhost', null, $uri);
        }

        return new static(null, null, null, null, null, $uri);
    }

    /**
     * Create a new instance from a local Windows path string.
     *
     * @return static
     */
    public static function createFromWindowsPath(string $uri = ''): self
    {
        $root = '';
        static $pattern = ',^(?<root>[a-zA-Z][:|\|]),';
        if (preg_match($pattern, $uri, $matches)) {
            $root = substr($matches['root'], 0, -1).':';
            $uri = substr($uri, strlen($root));
        }

        $uri = implode('/', array_map('rawurlencode', explode('\\', $uri)));

        //Local Windows absolute path
        if ('' !== $root) {
            return new static('file', null, null, 'localhost', null, '/'.$root.$uri);
        }

        //UNC Windows Path
        if ('//' === substr($uri, 0, 2)) {
            $parts = explode('/', substr($uri, 2), 2) + [1 => null];
            return new static('file', null, null, $parts[0], null, '/'.$parts[1]);
        }

        return new static(null, null, null, null, null, $uri);
    }
}
