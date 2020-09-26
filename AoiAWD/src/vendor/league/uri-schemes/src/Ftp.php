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
 * Immutable Value object representing a FTP Uri.
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since      1.2.0
 */
class Ftp extends AbstractUri
{
    /**
     * @inheritdoc
     */
    protected static $supported_schemes = [
        'ftp' => 21,
    ];

    /**
     * Tell whether the FTP URI is in valid state.
     *
     * A valid FTP URI:
     *
     * <ul>
     * <li>can be schemeless or supports only 'ftp' scheme
     * <li>can not contain a fragment component
     * <li>can not contain a query component
     * <li>has the same validation rules as an HTTP(s) URI
     * </ul>
     *
     * @see https://tools.ietf.org/html/rfc1738#section-3.2
     */
    protected function isValidUri(): bool
    {
        return null === $this->query
            && null === $this->fragment
            && '' !== $this->host
            && (null === $this->scheme || isset(static::$supported_schemes[$this->scheme]))
            && !('' != $this->scheme && null === $this->host);
    }
}
