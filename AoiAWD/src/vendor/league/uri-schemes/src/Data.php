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

use function array_filter;
use function base64_decode;
use function base64_encode;
use function count;
use function explode;
use function file_exists;
use function file_get_contents;
use function is_readable;
use function preg_match;
use function sprintf;
use function strpos;
use function strtolower;
use function substr;

/**
 * Immutable Value object representing a Data Uri.
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since      1.2.0
 */
class Data extends AbstractUri
{
    /**
     * @inheritdoc
     */
    protected static $supported_schemes = [
        'data' => null,
    ];

    /**
     * Tell whether the Data URI is in valid state.
     *
     * A valid Data URI:
     *
     * <ul>
     * <li>scheme is 'data'
     * <li>only contains a scheme and a path component
     * </ul>
     *
     * @see https://tools.ietf.org/html/rfc2397#section-3
     */
    protected function isValidUri(): bool
    {
        return 'data' === $this->scheme
            && null === $this->authority
            && null === $this->query
            && null === $this->fragment;
    }

    /**
     * Filter the Path component.
     *
     * @see https://tools.ietf.org/html/rfc2397
     *
     * @throws UriException If the path is not compliant with RFC2397
     */
    protected function filterPath(string $path): string
    {
        if ('' === $path) {
            return 'text/plain;charset=us-ascii,';
        }

        static $idn_pattern = '/[^\x20-\x7f]/';
        if (preg_match($idn_pattern, $path) || false === strpos($path, ',')) {
            throw new UriException(sprintf('The submitted path `%s` is invalid according to RFC2937', $path));
        }

        $parts = explode(',', $path, 2) + [1 => null];
        $mediatype = explode(';', $parts[0], 2) + [1 => null];
        $data = $parts[1];
        $mimetype = $mediatype[0];
        if (null === $mimetype || '' === $mimetype) {
            $mimetype = 'text/plain';
        }

        $parameters = $mediatype[1];
        if (null === $parameters || '' === $parameters) {
            $parameters = 'charset=us-ascii';
        }

        $this->assertValidPath($mimetype, $parameters, $data);

        return $this->formatPath($mimetype.';'.$parameters.','.$data);
    }

    /**
     * Assert the path is a compliant with RFC2397.
     *
     * @see https://tools.ietf.org/html/rfc2397
     *
     * @throws UriException If the mediatype or the data are not compliant
     *                      with the RFC2397
     */
    protected function assertValidPath(string $mimetype, string $parameters, string $data)
    {
        if (!preg_match(',^\w+/[-.\w]+(?:\+[-.\w]+)?$,', $mimetype)) {
            throw new UriException(sprintf('The path mimetype `%s` is invalid', $mimetype));
        }

        $is_binary = preg_match(',(;|^)base64$,', $parameters, $matches);
        if ($is_binary) {
            $parameters = substr($parameters, 0, - strlen($matches[0]));
        }

        $res = array_filter(array_filter(explode(';', $parameters), [$this, 'validateParameter']));
        if (!empty($res)) {
            throw new UriException(sprintf('The path paremeters `%s` contains is invalid', $parameters));
        }

        if (!$is_binary) {
            return;
        }

        $res = base64_decode($data, true);
        if (false === $res || $data !== base64_encode($res)) {
            throw new UriException(sprintf('The submitted path data `%s` is invalid', $data));
        }
    }

    /**
     * Validate mediatype parameter.
     */
    protected function validateParameter(string $parameter): bool
    {
        $properties = explode('=', $parameter);

        return 2 != count($properties) || strtolower($properties[0]) === 'base64';
    }

    /**
     * Create a new instance from a file path.
     *
     * @return static
     */
    public static function createFromPath(string $path): self
    {
        if (!file_exists($path) || !is_readable($path)) {
            throw new UriException(sprintf('The specified file `%s` does not exist or is not readable', $path));
        }

        $mimetype = str_replace(' ', '', (new \finfo(FILEINFO_MIME))->file($path));

        return new static(
            'data',
            null,
            null,
            null,
            null,
            $mimetype.';base64,'.base64_encode(file_get_contents($path))
        );
    }
}
