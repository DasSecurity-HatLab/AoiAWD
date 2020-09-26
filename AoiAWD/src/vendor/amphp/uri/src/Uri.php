<?php

namespace Amp\Uri;

/**
 * Provides URI parsing and can resolve URIs.
 */
final class Uri {
    private $defaultPortMap = [
        "http" => 80,
        "https" => 443,
        "ftp" => 21,
        "ftps" => 990,
        "smtp" => 25,
    ];

    private $uri;
    private $scheme = '';
    private $user = '';
    private $pass = '';
    private $host = '';
    private $port = 0;
    private $path = '';
    private $query = '';
    private $fragment = '';
    private $queryParameters = [];
    private $isIpV4 = false;
    private $isIpV6 = false;

    public function __construct(string $uri) {
        /** @var false|array $parts */
        if (!$parts = parse_url($uri)) {
            throw new InvalidUriException(
                'Invalid URI specified at ' . self::class . '::__construct Argument 1: ' . $uri
            );
        }

        $this->uri = $uri;

        foreach ($parts as $key => $value) {
            $this->{$key} = $value;
        }

        // http://www.apps.ietf.org/rfc/rfc3986.html#sec-3.1
        // "schemes are case-insensitive"
        $this->scheme = \strtolower($this->scheme);

        // http://www.apps.ietf.org/rfc/rfc3986.html#sec-3.2.2
        // "Although host is case-insensitive, producers and normalizers should use lowercase for
        // registered names and hexadecimal addresses for the sake of uniformity"
        if ($inAddr = @\inet_pton(\trim($this->host, "[]"))) {
            $this->host = \strtolower($this->host);

            if (isset($inAddr[4])) {
                $this->isIpV6 = true;
            } else {
                $this->isIpV4 = true;
            }
        } elseif ($this->host) {
            try {
                $this->host = normalizeDnsName($this->host);
            } catch (InvalidDnsNameException $e) {
                throw new InvalidUriException("Invalid URI: Invalid host: {$this->host}", 0, $e);
            }
        }

        if ($this->port === 0) {
            if (isset($this->defaultPortMap[$this->scheme])) {
                $this->port = $this->defaultPortMap[$this->scheme];
            }
        }

        $this->parseQueryParameters();

        if ($this->fragment) {
            $this->fragment = rawurldecode($this->fragment);
            $this->fragment = rawurlencode($this->fragment);
        }
    }

    public function __toString() {
        return $this->reconstitute(
            $this->scheme,
            $this->getAuthority(),
            $this->path,
            $this->query,
            $this->fragment
        );
    }

    /**
     * @see http://tools.ietf.org/html/rfc3986#section-5.3
     */
    private function reconstitute($scheme, $authority, $path, $query, $fragment): string {
        $result = '';

        if ($scheme) {
            $result .= $scheme . ':';
        }

        if ($authority) {
            $result .= '//';
            $result .= $authority;
        }

        $result .= $path;

        if ($query) {
            $result .= '?';
            $result .= $query;
        }

        if ($fragment) {
            $result .= '#';
            $result .= $fragment;
        }

        return $result;
    }

    /**
     * Normalizes the URI for maximal comparison success.
     *
     * @return string
     */
    public function normalize(): string {
        if (!$this->uri) {
            return '';
        }

        $path = $this->path ?: '/';
        $path = $this->removeDotSegments($path);
        $path = $this->decodeUnreservedCharacters($path);
        $path = $this->decodeReservedSubDelimiters($path);

        return $this->reconstitute(
            $this->scheme,
            $this->getAuthority(),
            $path,
            $this->query,
            $this->fragment
        );
    }

    /**
     * "URI producers and normalizers should omit the port component and its ":" delimiter if port
     * is empty or if its value would be the same as that of the scheme's default".
     *
     * @see http://www.apps.ietf.org/rfc/rfc3986.html#sec-3.2.3
     */
    private function getNormalizedDefaultPort(): string {
        if ($this->port === 0) {
            return "";
        }

        if (isset($this->defaultPortMap[$this->scheme])) {
            $defaultPort = $this->defaultPortMap[$this->scheme];

            if ($defaultPort === $this->port) {
                return "";
            }
        }

        return ":" . $this->port;
    }

    /**
     * @param string $input
     *
     * @return string
     *
     * @link http://www.apps.ietf.org/rfc/rfc3986.html#sec-5.2.4
     */
    private function removeDotSegments(string $input): string {
        $output = '';

        $patternA = ',^(\.\.?/),';
        $patternB1 = ',^(/\./),';
        $patternB2 = ',^(/\.)$,';
        $patternC = ',^(/\.\./|/\.\.),';
        // $patternD  = ',^(\.\.?)$,';
        $patternE = ',(/*[^/]*),';

        while ($input !== '') {
            if (\preg_match($patternA, $input)) {
                $input = \preg_replace($patternA, '', $input);
            } elseif (\preg_match($patternB1, $input, $match) || \preg_match($patternB2, $input, $match)) {
                $input = preg_replace(",^" . $match[1] . ",", '/', $input);
            } elseif (\preg_match($patternC, $input, $match)) {
                $input = \preg_replace(',^' . \preg_quote($match[1], ',') . ',', '/', $input);
                $output = \preg_replace(',/([^/]+)$,', '', $output);
            } elseif ($input === '.' || $input === '..') { // pattern D
                $input = '';
            } elseif (\preg_match($patternE, $input, $match)) {
                $initialSegment = $match[1];
                $input = \preg_replace(',^' . \preg_quote($initialSegment, ',') . ',', '', $input, 1);
                $output .= $initialSegment;
            }
        }

        return $output;
    }

    /**
     * @see http://www.apps.ietf.org/rfc/rfc3986.html#sec-2.3
     */
    private function decodeUnreservedCharacters($str) {
        $str = \rawurldecode($str);
        $str = \rawurlencode($str);

        $encoded = ['%2F', '%3A', '%40'];
        $decoded = ['/', ':', '@'];

        return \str_replace($encoded, $decoded, $str);
    }

    /**
     * @see http://www.apps.ietf.org/rfc/rfc3986.html#sec-2.2
     */
    private function decodeReservedSubDelimiters($str) {
        $encoded = ['%21', '%24', '%26', '%27', '%28', '%29', '%2A', '%2B', '%2C', '%3B', '%3D'];
        $decoded = ['!', '$', '&', "'", '(', ')', '*', '+', ',', ';', '='];

        return \str_replace($encoded, $decoded, $str);
    }

    /**
     * @param string $toResolve
     *
     * @return Uri
     *
     * @see http://tools.ietf.org/html/rfc3986#section-5.2.2
     */
    public function resolve(string $toResolve) {
        $r = new Uri($toResolve);

        if ((string) $r === '') {
            return clone $this;
        }

        $base = $this;

        $t = new \stdClass;
        $t->scheme = '';
        $t->authority = '';
        $t->path = '';
        $t->query = '';
        $t->fragment = '';

        if ('' !== $r->getScheme()) {
            $t->scheme = $r->getScheme();
            $t->authority = $r->getAuthority();
            $t->path = $this->removeDotSegments($r->getPath());
            $t->query = $r->getQuery();
        } else {
            if ('' !== $r->getAuthority()) {
                $t->authority = $r->getAuthority();
                $t->path = $this->removeDotSegments($r->getPath());
                $t->query = $r->getQuery();
            } else {
                if ('' === $r->getPath()) {
                    $t->path = $base->getPath();
                    if ($r->getQuery()) {
                        $t->query = $r->getQuery();
                    } else {
                        $t->query = $base->getQuery();
                    };
                } else {
                    if ($r->getPath() && substr($r->getPath(), 0, 1) === "/") {
                        $t->path = $this->removeDotSegments($r->getPath());
                    } else {
                        $t->path = $this->mergePaths($base->getPath(), $r->getPath());
                    };
                    $t->query = $r->getQuery();
                };
                $t->authority = $base->getAuthority();
            };
            $t->scheme = $base->getScheme();
        };

        $t->fragment = $r->getFragment();

        $result = $this->reconstitute($t->scheme, $t->authority, $t->path, $t->query, $t->fragment);

        return new Uri($result);
    }

    /**
     * @link http://tools.ietf.org/html/rfc3986#section-5.2.3
     */
    private function mergePaths($basePath, $pathToMerge) {
        if ($basePath === '') {
            $merged = '/' . $pathToMerge;
        } else {
            $parts = \explode('/', $basePath);
            \array_pop($parts);
            $parts[] = $pathToMerge;
            $merged = \implode('/', $parts);
        }

        return $this->removeDotSegments($merged);
    }

    /**
     * @return string
     */
    public function getScheme(): string {
        return $this->scheme;
    }

    /**
     * @return string
     */
    public function getUser(): string {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getPass(): string {
        return $this->pass;
    }

    /**
     * @return string
     */
    public function getHost(): string {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort(): int {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getPath(): string {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getQuery(): string {
        return $this->query;
    }

    /**
     * @return string
     */
    public function getFragment(): string {
        return $this->fragment;
    }

    /**
     * Retrieve the URI without the fragment component.
     */
    public function getAbsoluteUri() {
        return $this->reconstitute(
            $this->scheme,
            $this->getAuthority(),
            $this->path,
            $this->query,
            $fragment = ''
        );
    }

    /**
     * @return bool
     */
    public function isIpV4(): bool {
        return $this->isIpV4;
    }

    /**
     * @return bool
     */
    public function isIpV6(): bool {
        return $this->isIpV6;
    }

    /**
     * @param bool $hiddenPass Whether to hide the password.
     *
     * @return string
     *
     * @see http://www.apps.ietf.org/rfc/rfc3986.html#sec-3.2
     */
    public function getAuthority(bool $hiddenPass = true): string {
        $authority = $this->user;
        $authority .= $this->pass !== ''
            ? (':' . ($hiddenPass ? '********' : $this->pass))
            : '';

        $authority .= $authority ? '@' : '';
        $authority .= $this->isIpV6 ? "[{$this->host}]" : $this->host;
        $authority .= $this->getNormalizedDefaultPort();

        return $authority;
    }

    private function parseQueryParameters() {
        if ($this->query) {
            $parameters = [];

            foreach (\explode("&", $this->query) as $pair) {
                $pair = explode("=", $pair, 2);
                $parameters[\urldecode($pair[0])][] = \urldecode($pair[1] ?? "");
            }

            $this->queryParameters = $parameters;
        }
    }

    /**
     * Check whether the specified query parameter exists.
     *
     * @param string $parameter
     *
     * @return bool
     */
    public function hasQueryParameter(string $parameter): bool {
        return isset($this->queryParameters[$parameter]);
    }

    /**
     * Get the first occurrence of the specified query parameter.
     *
     * @param string $parameter
     *
     * @return string|null
     */
    public function getQueryParameter(string $parameter) {
        return $this->queryParameters[$parameter][0] ?? null;
    }

    /**
     * Get all occurrences of the specified query parameter.
     *
     * @param string $parameter
     *
     * @return string[]
     */
    public function getQueryParameterArray(string $parameter): array {
        return $this->queryParameters[$parameter] ?? [];
    }

    /**
     * @return array
     */
    public function getAllQueryParameters(): array {
        return $this->queryParameters;
    }

    /**
     * @return string
     */
    public function getOriginalUri(): string {
        return $this->uri;
    }

    /**
     * Test whether the specified string is a valid URI.
     *
     * @param string $uri
     *
     * @return bool
     */
    public static function isValid(string $uri): bool {
        try {
            new self($uri);
        } catch (InvalidUriException $e) {
            return false;
        }

        return true;
    }
}
