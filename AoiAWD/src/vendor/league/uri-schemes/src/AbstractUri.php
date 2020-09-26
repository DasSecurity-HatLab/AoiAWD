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

use BadMethodCallException;
use League\Uri\Interfaces\Uri as DeprecatedLeagueUriInterface;
use UnexpectedValueException;
use function array_keys;
use function defined;
use function explode;
use function filter_var;
use function function_exists;
use function idn_to_ascii;
use function implode;
use function in_array;
use function inet_pton;
use function preg_match;
use function preg_replace_callback;
use function rawurldecode;
use function rawurlencode;
use function sprintf;
use function strpos;
use function strtolower;
use function substr;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;

/**
 * common URI Object properties and methods.
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since      1.2.0
 */
abstract class AbstractUri implements UriInterface, DeprecatedLeagueUriInterface
{
    /**
     * @deprecated 1.2.0 will be removed in the next major point release
     *
     * Invalid Characters
     *
     * @see http://tools.ietf.org/html/rfc3986#section-2
     *
     * @var string
     */
    const INVALID_CHARS = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x7F";

    /**
     * RFC3986 Sub delimiter characters regular expression pattern.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-2.2
     *
     * @var string
     */
    const REGEXP_CHARS_SUBDELIM = "\!\$&'\(\)\*\+,;\=%";

    /**
     * RFC3986 unreserved characters regular expression pattern.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-2.3
     *
     * @var string
     */
    const REGEXP_CHARS_UNRESERVED = 'A-Za-z0-9_\-\.~';

    /**
     * URI scheme component.
     *
     * @var string|null
     */
    protected $scheme;

    /**
     * URI user info part.
     *
     * @var string|null
     */
    protected $user_info;

    /**
     * URI host component.
     *
     * @var string|null
     */
    protected $host;

    /**
     * URI port component.
     *
     * @var int|null
     */
    protected $port;

    /**
     * URI authority string representation.
     *
     * @var string|null
     */
    protected $authority;

    /**
     * URI path component.
     *
     * @var string
     */
    protected $path = '';

    /**
     * URI query component.
     *
     * @var string|null
     */
    protected $query;

    /**
     * URI fragment component.
     *
     * @var string|null
     */
    protected $fragment;

    /**
     * URI string representation.
     *
     * @var string|null
     */
    protected $uri;

    /**
     * Supported schemes and corresponding default port.
     *
     * @var array
     */
    protected static $supported_schemes;

    /**
     * Static method called by PHP's var export.
     *
     * @return static
     */
    public static function __set_state(array $components): self
    {
        $components['user'] = null;
        $components['pass'] = null;
        if (null !== $components['user_info']) {
            list($components['user'], $components['pass']) = explode(':', $components['user_info'], 2) + [1 => null];
        }

        return new static(
            $components['scheme'],
            $components['user'],
            $components['pass'],
            $components['host'],
            $components['port'],
            $components['path'],
            $components['query'],
            $components['fragment']
        );
    }

    /**
     * Create a new instance from a string.
     *
     * @return static
     */
    public static function createFromString(string $uri = ''): self
    {
        $components = parse($uri);

        return new static(
            $components['scheme'],
            $components['user'],
            $components['pass'],
            $components['host'],
            $components['port'],
            $components['path'],
            $components['query'],
            $components['fragment']
        );
    }

    /**
     * Create a new instance from a hash of parse_url parts.
     *
     * @param array $components a hash representation of the URI similar
     *                          to PHP parse_url function result
     *
     * @return static
     */
    public static function createFromComponents(array $components = []): self
    {
        $components += [
            'scheme' => null, 'user' => null, 'pass' => null, 'host' => null,
            'port' => null, 'path' => '', 'query' => null, 'fragment' => null,
        ];

        return new static(
            $components['scheme'],
            $components['user'],
            $components['pass'],
            $components['host'],
            $components['port'],
            $components['path'],
            $components['query'],
            $components['fragment']
        );
    }

    /**
     * Create a new instance.
     *
     */
    protected function __construct(
        string $scheme = null,
        string $user = null,
        string $pass = null,
        string $host = null,
        int $port = null,
        string $path = '',
        string $query = null,
        string $fragment = null
    ) {
        $this->scheme = $this->formatScheme($scheme);
        $this->user_info = $this->formatUserInfo($user, $pass);
        $this->host = $this->formatHost($host);
        $this->port = $this->formatPort($port);
        $this->authority = $this->setAuthority();
        $this->path = $this->filterPath($path);
        $this->query = $this->formatQueryAndFragment($query);
        $this->fragment = $this->formatQueryAndFragment($fragment);
        $this->assertValidState();
    }

    /**
     * Format the Scheme and Host component.
     *
     *
     * @return string|null
     */
    protected function formatScheme(string $scheme = null)
    {
        if ('' === $scheme || null === $scheme) {
            return $scheme;
        }

        $formatted_scheme = strtolower($scheme);
        static $pattern = '/^[a-z][a-z0-9\+\.\-]*$/';
        if (preg_match($pattern, $formatted_scheme)) {
            return $formatted_scheme;
        }

        throw new UriException(sprintf('The submitted scheme `%s` is invalid', $scheme));
    }

    /**
     * Set the UserInfo component.
     *
     * @return string|null
     */
    protected static function formatUserInfo(string $user = null, string $password = null)
    {
        if (null === $user) {
            return $user;
        }

        static $user_pattern = '/(?:[^%'.self::REGEXP_CHARS_UNRESERVED.self::REGEXP_CHARS_SUBDELIM.']++|%(?![A-Fa-f0-9]{2}))/';
        $user = preg_replace_callback($user_pattern, [AbstractUri::class, 'urlEncodeMatch'], $user);
        if (null === $password) {
            return $user;
        }

        static $password_pattern = '/(?:[^%:'.self::REGEXP_CHARS_UNRESERVED.self::REGEXP_CHARS_SUBDELIM.']++|%(?![A-Fa-f0-9]{2}))/';

        return $user.':'.preg_replace_callback($password_pattern, [AbstractUri::class, 'urlEncodeMatch'], $password);
    }

    /**
     * Returns the RFC3986 encoded string matched.
     */
    protected static function urlEncodeMatch(array $matches): string
    {
        return rawurlencode($matches[0]);
    }

    /**
     * Validate and Format the Host component.
     *
     * @param string|null $host
     *
     * @return string|null
     */
    protected function formatHost($host)
    {
        if (null === $host || '' === $host) {
            return $host;
        }

        if ('[' !== $host[0]) {
            return $this->formatRegisteredName($host);
        }

        return $this->formatIp($host);
    }

    /**
     * Validate and format a registered name.
     *
     * The host is converted to its ascii representation if needed
     *
     * @throws UriException if the submitted host is not a valid registered name
     */
    private function formatRegisteredName(string $host): string
    {
        $formatted_host = rawurldecode(strtolower($host));

        static $reg_name = '/^(
            (?<unreserved>[a-z0-9_~\-\.])|
            (?<sub_delims>[!$&\'()*+,;=])|
            (?<encoded>%[A-F0-9]{2})
        )+$/x';
        if (preg_match($reg_name, $formatted_host)) {
            return $formatted_host;
        }

        //to test IDN host non-ascii characters must be present in the host
        static $idn_pattern = '/[^\x20-\x7f]/';
        if (!preg_match($idn_pattern, $formatted_host)) {
            throw new UriException(sprintf('Host `%s` is invalid : the registered name is malformed', $host));
        }

        static $idn_support = null;
        $idn_support = $idn_support ?? function_exists('idn_to_ascii') && defined('INTL_IDNA_VARIANT_UTS46');
        if (!$idn_support) {
            // @codeCoverageIgnoreStart
            // added because it is not possible in travis to disabled the ext/intl extension
            // see travis issue https://github.com/travis-ci/travis-ci/issues/4701
            throw new MissingIdnSupport(sprintf('the host `%s` could not be processed for IDN. Verify that ext/intl is installed for IDN support and that ICU is at least version 4.6.', $host));
            // @codeCoverageIgnoreEnd
        }

        $formatted_host = idn_to_ascii($formatted_host, 0, INTL_IDNA_VARIANT_UTS46, $arr);
        if (0 !== $arr['errors']) {
            throw new UriException(sprintf('Host `%s` is invalid : %s', $host, $this->getIdnaErrorMessage($arr['errors'])));
        }

        // @codeCoverageIgnoreStart
        if (false === $formatted_host) {
            throw new UnexpectedValueException(sprintf('The Intl extension is misconfigured for %s, please correct this issue before proceeding.', PHP_OS));
        }
        // @codeCoverageIgnoreEnd

        return $formatted_host;
    }

    /**
     * Retrieves and format IDNA conversion error message.
     *
     * @see http://icu-project.org/apiref/icu4j/com/ibm/icu/text/IDNA.Error.html
     */
    private function getIdnaErrorMessage(int $error_byte): string
    {
        /**
         * IDNA errors.
         */
        static $idn_errors = [
            IDNA_ERROR_EMPTY_LABEL => 'a non-final domain name label (or the whole domain name) is empty',
            IDNA_ERROR_LABEL_TOO_LONG => 'a domain name label is longer than 63 bytes',
            IDNA_ERROR_DOMAIN_NAME_TOO_LONG => 'a domain name is longer than 255 bytes in its storage form',
            IDNA_ERROR_LEADING_HYPHEN => 'a label starts with a hyphen-minus ("-")',
            IDNA_ERROR_TRAILING_HYPHEN => 'a label ends with a hyphen-minus ("-")',
            IDNA_ERROR_HYPHEN_3_4 => 'a label contains hyphen-minus ("-") in the third and fourth positions',
            IDNA_ERROR_LEADING_COMBINING_MARK => 'a label starts with a combining mark',
            IDNA_ERROR_DISALLOWED => 'a label or domain name contains disallowed characters',
            IDNA_ERROR_PUNYCODE => 'a label starts with "xn--" but does not contain valid Punycode',
            IDNA_ERROR_LABEL_HAS_DOT => 'a label contains a dot=full stop',
            IDNA_ERROR_INVALID_ACE_LABEL => 'An ACE label does not contain a valid label string',
            IDNA_ERROR_BIDI => 'a label does not meet the IDNA BiDi requirements (for right-to-left characters)',
            IDNA_ERROR_CONTEXTJ => 'a label does not meet the IDNA CONTEXTJ requirements',
        ];

        $res = [];
        foreach ($idn_errors as $error => $reason) {
            if ($error_byte & $error) {
                $res[] = $reason;
            }
        }

        return empty($res) ? 'Unknown IDNA conversion error.' : implode(', ', $res).'.';
    }

    /**
     * Validate and Format the IPv6/IPvfuture host.
     *
     * @throws UriException if the submitted host is not a valid IPv6
     */
    private function formatIp(string $host): string
    {
        $ip = substr($host, 1, -1);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $host;
        }

        static $ip_future = '/^
            v(?<version>[A-F0-9])+\.
            (?:
                (?<unreserved>[a-z0-9_~\-\.])|
                (?<sub_delims>[!$&\'()*+,;=:])  # also include the : character
            )+
        $/ix';
        if (preg_match($ip_future, $ip, $matches) && !in_array($matches['version'], ['4', '6'], true)) {
            return $host;
        }

        if (false === ($pos = strpos($ip, '%'))) {
            throw new UriException(sprintf('Host `%s` is invalid : the IP host is malformed', $host));
        }

        static $gen_delims = '/[:\/?#\[\]@ ]/'; // Also includes space.
        if (preg_match($gen_delims, rawurldecode(substr($ip, $pos)))) {
            throw new UriException(sprintf('Host `%s` is invalid : the IP host is malformed', $host));
        }

        $ip = substr($ip, 0, $pos);
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw new UriException(sprintf('Host `%s` is invalid : the IP host is malformed', $host));
        }

        //Only the address block fe80::/10 can have a Zone ID attach to
        //let's detect the link local significant 10 bits
        static $address_block = "\xfe\x80";

        if (substr(inet_pton($ip) & $address_block, 0, 2) === $address_block) {
            return $host;
        }

        throw new UriException(sprintf('Host `%s` is invalid : the IP host is malformed', $host));
    }

    /**
     * Format the Port component.
     *
     * @param int|null $port
     *
     * @return int|null
     */
    protected function formatPort($port)
    {
        $port = $this->filterPort($port);

        if (isset(static::$supported_schemes[$this->scheme])
            && static::$supported_schemes[$this->scheme] === $port) {
            return null;
        }

        return $port;
    }

    /**
     * Filter the Port component.
     *
     * @param int|null $port
     *
     * @throws UriException if the port is invalid
     *
     * @return int|null
     */
    protected static function filterPort($port)
    {
        if (null === $port) {
            return $port;
        }

        if ($port < 0) {
            throw UriException::createFromInvalidPort($port);
        }

        return $port;
    }

    /**
     * Generate the URI authority part.
     *
     * @return string|null
     */
    protected function setAuthority()
    {
        $authority = null;
        if (null !== $this->user_info) {
            $authority = $this->user_info.'@';
        }

        if (null !== $this->host) {
            $authority .= $this->host;
        }

        if (null !== $this->port) {
            $authority .= ':'.$this->port;
        }

        return $authority;
    }

    /**
     * Filter the Path component.
     */
    protected function filterPath(string $path): string
    {
        return $this->formatPath($path);
    }

    /**
     * Format the Path component.
     */
    protected function formatPath(string $path): string
    {
        static $pattern = '/(?:[^'.self::REGEXP_CHARS_UNRESERVED.self::REGEXP_CHARS_SUBDELIM.'%:@\/}{]++|%(?![A-Fa-f0-9]{2}))/';
        return preg_replace_callback($pattern, [AbstractUri::class, 'urlEncodeMatch'], $path);
    }

    /**
     * Format the Query or the Fragment component.
     *
     * Returns a array containing:
     * <ul>
     * <li> the formatted component (a string or null)</li>
     * <li> a boolean flag telling wether the delimiter is to be added to the component
     * when building the URI string representation</li>
     * </ul>
     *
     * @param string|null $component
     *
     * @return string|null
     */
    protected function formatQueryAndFragment($component)
    {
        if (null === $component || '' === $component) {
            return $component;
        }

        static $pattern = '/(?:[^'.self::REGEXP_CHARS_UNRESERVED.self::REGEXP_CHARS_SUBDELIM.'%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/';
        return preg_replace_callback($pattern, [AbstractUri::class, 'urlEncodeMatch'], $component);
    }

    /**
     * assert the URI internal state is valid.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     *
     * @throws UriException if the URI is in an invalid state according to RFC3986
     * @throws UriException if the URI is in an invalid state according to scheme specific rules
     */
    protected function assertValidState()
    {
        $this->uri = null;

        if (null !== $this->authority && ('' !== $this->path && '/' !== $this->path[0])) {
            throw new UriException(
                'Invalid URI: if an authority is present the path must be empty or start with a `/`'
            );
        }

        if (null === $this->authority && 0 === strpos($this->path, '//')) {
            throw new UriException(
                'Invalid URI: if there is no authority the path `%s` can not start with a `//`'
            );
        }

        if (null === $this->authority
            && null === $this->scheme
            && false !== ($pos = strpos($this->path, ':'))
            && false === strpos(substr($this->path, 0, $pos), '/')
        ) {
            throw new UriException(
                'Invalid URI: in absence of a scheme and an authority the first path segment cannot contain a colon (":") character.'
            );
        }

        if (!$this->isValidUri()) {
            throw new UriException(sprintf(
                'Invalid URI: The submitted uri `%s` is invalid for the following scheme(s): `%s`',
                $this->getUriString($this->scheme, $this->authority, $this->path, $this->query, $this->fragment),
                implode(', ', array_keys(static::$supported_schemes))
            ));
        }
    }

    /**
     * Tell whether the current URI is in valid state.
     *
     * The URI object validity depends on the scheme. This method
     * MUST be implemented on every URI object
     */
    abstract protected function isValidUri(): bool;

    /**
     * Generate the URI string representation from its components.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-5.3
     */
    protected function getUriString(
        string $scheme = null,
        string $authority = null,
        string $path = '',
        string $query = null,
        string $fragment = null
    ): string {
        if (null !== $scheme) {
            $scheme = $scheme.':';
        }

        if (null !== $authority) {
            $authority = '//'.$authority;
        }

        if (null !== $query) {
            $query = '?'.$query;
        }

        if (null !== $fragment) {
            $fragment = '#'.$fragment;
        }

        return $scheme.$authority.$path.$query.$fragment;
    }

    /**
     * Return the string representation as a URI reference.
     *
     * Depending on which components of the URI are present, the resulting
     * string is either a full URI or relative reference according to RFC 3986,
     * Section 4.1. The method concatenates the various components of the URI,
     * using the appropriate delimiters:
     *
     * - If a scheme is present, it MUST be suffixed by ":".
     * - If an authority is present, it MUST be prefixed by "//".
     * - The path can be concatenated without delimiters.
     * - If a query is present, it MUST be prefixed by "?".
     * - If a fragment is present, it MUST be prefixed by "#".
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     */
    public function __toString()
    {
        $this->uri = $this->uri ?? $this->getUriString(
            $this->scheme,
            $this->authority,
            $this->path,
            $this->query,
            $this->fragment
        );

        return $this->uri;
    }

    /**
     * Retrieve the scheme component of the URI.
     *
     * If no scheme is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.1.
     *
     * The trailing ":" character is not part of the scheme and MUST NOT be
     * added.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     */
    public function getScheme(): string
    {
        return (string) $this->scheme;
    }

    /**
     * Retrieve the authority component of the URI.
     *
     * If no authority information is present, this method MUST return an empty
     * string.
     *
     * The authority syntax of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     */
    public function getAuthority(): string
    {
        return (string) $this->authority;
    }

    /**
     * Retrieve the user information component of the URI.
     *
     * If no user information is present, this method MUST return an empty
     * string.
     *
     * The userinfo syntax of the URI is:
     *
     * <pre>
     * username[:password]
     * </pre>
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * The trailing "@" character is not part of the user information and MUST
     * NOT be added.
     */
    public function getUserInfo(): string
    {
        return (string) $this->user_info;
    }

    /**
     * Retrieve the host component of the URI.
     *
     * If no host is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.2.2.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     */
    public function getHost(): string
    {
        return (string) $this->host;
    }

    /**
     * Retrieve the port component of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer. If the port is the standard port
     * used with the current scheme, this method SHOULD return null.
     *
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     *
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     *
     * @return null|int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Retrieve the path component of the URI.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * Normally, the empty path "" and absolute path "/" are considered equal as
     * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
     * do this normalization because in contexts with a trimmed base path, e.g.
     * the front controller, this difference becomes significant. It's the task
     * of the user to handle both "" and "/".
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.3.
     *
     * As an example, if the value should include a slash ("/") not intended as
     * delimiter between path segments, that value MUST be passed in encoded
     * form (e.g., "%2F") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Retrieve the query string of the URI.
     *
     * If no query string is present, this method MUST return an empty string.
     *
     * The leading "?" character is not part of the query and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.4.
     *
     * As an example, if a value in a key/value pair of the query string should
     * include an ampersand ("&") not intended as a delimiter between values,
     * that value MUST be passed in encoded form (e.g., "%26") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     */
    public function getQuery(): string
    {
        return (string) $this->query;
    }

    /**
     * Retrieve the fragment component of the URI.
     *
     * If no fragment is present, this method MUST return an empty string.
     *
     * The leading "#" character is not part of the fragment and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.5.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     */
    public function getFragment(): string
    {
        return (string) $this->fragment;
    }

    /**
     * Return an instance with the specified scheme.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     *
     * An empty scheme is equivalent to removing the scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     *
     * @throws UriException for transformations that would result in
     *                      a state that cannot be represented as a
     *                      valid URI reference.
     * @return static
     */
    public function withScheme($scheme): self
    {
        $scheme = $this->formatScheme($this->filterString($scheme));
        if ('' === $scheme) {
            $scheme = null;
        }

        if ($scheme === $this->scheme) {
            return $this;
        }

        $clone = clone $this;
        $clone->scheme = $scheme;
        $clone->port = $clone->formatPort($clone->port);
        $clone->authority = $clone->setAuthority();
        $clone->assertValidState();

        return $clone;
    }

    /**
     * Filter a string.
     *
     *
     * @throws UriException if the submitted data can not be converted to string
     */
    protected static function filterString(string $str): string
    {
        static $pattern = '/[\x00-\x1f\x7f]/';
        if (!preg_match($pattern, $str)) {
            return $str;
        }

        throw UriException::createFromInvalidCharacters($str);
    }

    /**
     * Return an instance with the specified user information.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     *
     * Password is optional, but the user information MUST include the
     * user; an empty string for the user is equivalent to removing user
     * information.
     *
     * @param string      $user
     * @param null|string $password
     *
     * @throws UriException for transformations that would result in
     *                      a state that cannot be represented as a
     *                      valid URI reference.
     * @return static
     */
    public function withUserInfo($user, $password = null): self
    {
        $user_info = null;
        if ('' !== $user) {
            $user_info = $this->formatUserInfo($user, $password);
        }

        if ($user_info === $this->user_info) {
            return $this;
        }

        $clone = clone $this;
        $clone->user_info = $user_info;
        $clone->authority = $clone->setAuthority();
        $clone->assertValidState();

        return $clone;
    }

    /**
     * Return an instance with the specified host.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified host.
     *
     * An empty host value is equivalent to removing the host.
     *
     * @param string $host
     *
     * @throws UriException for transformations that would result in
     *                      a state that cannot be represented as a
     *                      valid URI reference.
     * @return static
     */
    public function withHost($host): self
    {
        $host = $this->formatHost($this->filterString($host));
        if ('' === $host) {
            $host = null;
        }

        if ($host === $this->host) {
            return $this;
        }

        $clone = clone $this;
        $clone->host = $host;
        $clone->authority = $clone->setAuthority();
        $clone->assertValidState();

        return $clone;
    }

    /**
     * Return an instance with the specified port.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified port.
     *
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     *
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param null|int $port The port to use with the new instance; a null value
     *                       removes the port information.
     *
     * @throws UriException for transformations that would result in
     *                      a state that cannot be represented as a
     *                      valid URI reference.
     * @return static
     */
    public function withPort($port): self
    {
        $port = $this->formatPort($port);
        if ($port === $this->port) {
            return $this;
        }

        $clone = clone $this;
        $clone->port = $port;
        $clone->authority = $clone->setAuthority();
        $clone->assertValidState();

        return $clone;
    }

    /**
     * Return an instance with the specified path.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified path.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * If the path is intended to be domain-relative rather than path relative then
     * it must begin with a slash ("/"). Paths not starting with a slash ("/")
     * are assumed to be relative to some base path known to the application or
     * consumer.
     *
     * Users can provide both encoded and decoded path characters.
     * Implementations ensure the correct encoding as outlined in getPath().
     *
     * @param string $path
     *
     * @throws UriException for transformations that would result in
     *                      a state that cannot be represented as a
     *                      valid URI reference.
     * @return static
     */
    public function withPath($path): self
    {
        $path = $this->filterPath($this->filterString($path));
        if ($path === $this->path) {
            return $this;
        }

        $clone = clone $this;
        $clone->path = $path;
        $clone->assertValidState();

        return $clone;
    }

    /**
     * Return an instance with the specified query string.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified query string.
     *
     * Users can provide both encoded and decoded query characters.
     * Implementations ensure the correct encoding as outlined in getQuery().
     *
     * An empty query string value is equivalent to removing the query string.
     *
     * @param string $query
     *
     * @throws UriException for transformations that would result in
     *                      a state that cannot be represented as a
     *                      valid URI reference.
     * @return static
     */
    public function withQuery($query): self
    {
        $query = $this->formatQueryAndFragment($this->filterString($query));
        if ('' === $query) {
            $query = null;
        }

        if ($query === $this->query) {
            return $this;
        }

        $clone = clone $this;
        $clone->query = $query;
        $clone->assertValidState();

        return $clone;
    }

    /**
     * Return an instance with the specified URI fragment.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified URI fragment.
     *
     * Users can provide both encoded and decoded fragment characters.
     * Implementations ensure the correct encoding as outlined in getFragment().
     *
     * An empty fragment value is equivalent to removing the fragment.
     *
     * @param string $fragment
     *
     * @throws UriException for transformations that would result in
     *                      a state that cannot be represented as a
     *                      valid URI reference.
     * @return static
     */
    public function withFragment($fragment): self
    {
        $fragment = $this->formatQueryAndFragment($this->filterString($fragment));
        if ('' === $fragment) {
            $fragment = null;
        }

        if ($fragment === $this->fragment) {
            return $this;
        }

        $clone = clone $this;
        $clone->fragment = $fragment;
        $clone->assertValidState();

        return $clone;
    }

    /**
     * @inheritdoc
     */
    public function __set(string $property, $value)
    {
        throw new BadMethodCallException(sprintf('"%s" is an undefined or inaccessible property', $property));
    }

    /**
     * @inheritdoc
     */
    public function __isset(string $property)
    {
        throw new BadMethodCallException(sprintf('"%s" is an undefined or inaccessible property', $property));
    }

    /**
     * @inheritdoc
     */
    public function __unset(string $property)
    {
        throw new BadMethodCallException(sprintf('"%s" is an undefined or inaccessible property', $property));
    }

    /**
     * @inheritdoc
     */
    public function __get(string $property)
    {
        throw new BadMethodCallException(sprintf('"%s" is an undefined or inaccessible property', $property));
    }
}
