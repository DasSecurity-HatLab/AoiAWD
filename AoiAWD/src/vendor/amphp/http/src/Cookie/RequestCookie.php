<?php

namespace Amp\Http\Cookie;

/**
 * A cookie as sent in a request's 'cookie' header, so without any attributes.
 *
 * This class does not deal with encoding of arbitrary names and values. If you want to use arbitrary values, please use
 * an encoding mechanism like Base64 or URL encoding.
 *
 * @link https://tools.ietf.org/html/rfc6265#section-5.4
 */
final class RequestCookie
{
    /** @var string */
    private $name;

    /** @var string */
    private $value;

    /**
     * Parses the cookies from a 'cookie' header.
     *
     * Note: Parsing is aborted if there's an invalid value and no cookies are returned.
     *
     * @param string $string Valid 'cookie' header line.
     *
     * @return RequestCookie[]
     */
    public static function fromHeader(string $string): array
    {
        $cookies = \explode(";", $string);
        $result = [];

        try {
            foreach ($cookies as $cookie) {
                $parts = \explode('=', $cookie, 2);

                if (2 !== \count($parts)) {
                    return [];
                }

                list($name, $value) = $parts;

                // We can safely trim quotes, as they're not allowed within cookie values
                $result[] = new self(\trim($name), \trim($value, " \t\""));
            }
        } catch (InvalidCookieException $e) {
            return [];
        }

        return $result;
    }

    /**
     * @param string $name Cookie name in its decoded form.
     * @param string $value Cookie value in its decoded form.
     *
     * @throws InvalidCookieException If name or value is invalid.
     */
    public function __construct(string $name, string $value = '')
    {
        if (!\preg_match('(^[^()<>@,;:\\\"/[\]?={}\x01-\x20\x7F]*+$)', $name)) {
            throw new InvalidCookieException("Invalid cookie name: '{$name}'");
        }

        if (!\preg_match('(^[\x21\x23-\x2B\x2D-\x3A\x3C-\x5B\x5D-\x7E]*+$)', $value)) {
            throw new InvalidCookieException("Invalid cookie value: '{$value}'");
        }

        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @return string Name of the cookie.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string Value of the cookie.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string Representation of the cookie as in a 'cookie' header.
     */
    public function __toString(): string
    {
        return $this->name . '=' . $this->value;
    }
}
