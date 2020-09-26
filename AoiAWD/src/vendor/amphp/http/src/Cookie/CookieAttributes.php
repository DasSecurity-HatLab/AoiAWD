<?php

namespace Amp\Http\Cookie;

/**
 * Cookie attributes as defined in https://tools.ietf.org/html/rfc6265.
 *
 * @link https://tools.ietf.org/html/rfc6265
 */
final class CookieAttributes
{
    /** @var string */
    private $path = '';

    /** @var string */
    private $domain = '';

    /** @var int|null */
    private $maxAge;

    /** @var \DateTimeImmutable */
    private $expiry;

    /** @var bool */
    private $secure = false;

    /** @var bool */
    private $httpOnly = true;

    /**
     * @return CookieAttributes No cookie attributes.
     *
     * @see self::default()
     */
    public static function empty(): self
    {
        $new = new self;
        $new->httpOnly = false;

        return $new;
    }

    /**
     * @return CookieAttributes Default cookie attributes, which means httpOnly is enabled by default.
     *
     * @see self::empty()
     */
    public static function default(): self
    {
        return new self;
    }

    private function __construct()
    {
        // only allow creation via named constructors
    }

    /**
     * @param string $path Cookie path.
     *
     * @return self Cloned instance with the specified operation applied. Cloned instance with the specified operation applied.
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.4
     */
    public function withPath(string $path): self
    {
        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    /**
     * @param string $domain Cookie domain.
     *
     * @return self Cloned instance with the specified operation applied.
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.3
     */
    public function withDomain(string $domain): self
    {
        $new = clone $this;
        $new->domain = $domain;

        return $new;
    }

    /**
     * Applies the given maximum age to the cookie.
     *
     * @param int $maxAge Cookie maximum age.
     *
     * @return self Cloned instance with the specified operation applied.
     *
     * @see self::withoutMaxAge()
     * @see self::withExpiry()
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.2
     */
    public function withMaxAge(int $maxAge): self
    {
        $new = clone $this;
        $new->maxAge = $maxAge;

        return $new;
    }

    /**
     * Removes any max-age information.
     *
     * @return self Cloned instance with the specified operation applied.
     *
     * @see self::withMaxAge()
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.2
     */
    public function withoutMaxAge(): self
    {
        $new = clone $this;
        $new->maxAge = null;

        return $new;
    }

    /**
     * Applies the given expiry to the cookie.
     *
     * @param \DateTimeInterface $date
     *
     * @return self Cloned instance with the specified operation applied.
     *
     * @see self::withMaxAge()
     * @see self::withoutExpiry()
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.1
     */
    public function withExpiry(\DateTimeInterface $date): self
    {
        $new = clone $this;

        if ($date instanceof \DateTimeImmutable) {
            $new->expiry = $date;
        } elseif ($date instanceof \DateTime) {
            $new->expiry = \DateTimeImmutable::createFromMutable($date);
        } else {
            $new->expiry = new \DateTimeImmutable("@" . $date->getTimestamp());
        }

        return $new;
    }

    /**
     * Removes any expiry information.
     *
     * @return self Cloned instance with the specified operation applied.
     *
     * @see self::withExpiry()
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.1
     */
    public function withoutExpiry(): self
    {
        $new = clone $this;
        $new->expiry = null;

        return $new;
    }

    /**
     * @return self Cloned instance with the specified operation applied.
     *
     * @see self::withoutSecure()
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.5
     */
    public function withSecure(): self
    {
        $new = clone $this;
        $new->secure = true;

        return $new;
    }

    /**
     * @return self Cloned instance with the specified operation applied.
     *
     * @see self::withSecure()
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.5
     */
    public function withoutSecure(): self
    {
        $new = clone $this;
        $new->secure = false;

        return $new;
    }

    /**
     * @return self Cloned instance with the specified operation applied.
     *
     * @see self::withoutHttpOnly()
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.6
     */
    public function withHttpOnly(): self
    {
        $new = clone $this;
        $new->httpOnly = true;

        return $new;
    }

    /**
     * @return self Cloned instance with the specified operation applied.
     *
     * @see self::withHttpOnly()
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.6
     */
    public function withoutHttpOnly(): self
    {
        $new = clone $this;
        $new->httpOnly = false;

        return $new;
    }

    /**
     * @return string Cookie path.
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.4
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string Cookie domain.
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.3
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @return int|null Cookie maximum age in seconds or `null` if no value is set.
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.2
     */
    public function getMaxAge()
    { /* : ?int */
        return $this->maxAge;
    }

    /**
     * @return \DateTimeImmutable|null Cookie expiry or `null` if no value is set.
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.2
     */
    public function getExpiry()
    { /* : ?\DateTimeImmutable */
        return $this->expiry;
    }

    /**
     * @return bool Whether the secure flag is enabled or not.
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.5
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }

    /**
     * @return bool Whether the httpOnly flag is enabled or not.
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.2.6
     */
    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    /**
     * @return string Representation of the cookie attributes appended to key=value in a 'set-cookie' header.
     */
    public function __toString(): string
    {
        $string = '';

        if ($this->expiry) {
            $string .= '; Expires=' . \gmdate('D, j M Y G:i:s T', $this->expiry->getTimestamp());
        }

        if ($this->maxAge) {
            $string .= '; Max-Age=' . $this->maxAge;
        }

        if ('' !== $this->path) {
            $string .= '; Path=' . $this->path;
        }

        if ('' !== $this->domain) {
            $string .= '; Domain=' . $this->domain;
        }

        if ($this->secure) {
            $string .= '; Secure';
        }

        if ($this->httpOnly) {
            $string .= '; HttpOnly';
        }

        return $string;
    }
}
