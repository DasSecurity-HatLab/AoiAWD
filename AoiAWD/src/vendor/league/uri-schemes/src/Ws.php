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

/**
 * Immutable Value object representing a Ws(s) Uri.
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since      1.2.0
 */
class Ws extends AbstractUri
{
    /**
     * @inheritdoc
     */
    protected static $supported_schemes = [
        'ws' => 80,
        'wss' => 443,
    ];

    /**
     * Tell whether the Ws(s) URI is in valid state according to RFC6455.
     *
     * A valid Ws(s) URI:
     *
     * <ul>
     * <li>can be schemeless or supports only 'ws' and 'wss' schemes
     * <li>can not contain a fragment component
     * <li>has the same validation rules as an HTTP(s) URI
     * </ul>
     *
     * @see https://tools.ietf.org/html/rfc6455#section-3
     */
    protected function isValidUri(): bool
    {
        return null === $this->fragment
            && '' !== $this->host
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
}
