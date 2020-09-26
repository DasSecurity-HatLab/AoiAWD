<?php

namespace Amp\Http\Cookie;

use PHPUnit\Framework\TestCase;

class ResponseCookieTest extends TestCase
{
    public function testParsingOnEmptyName()
    {
        $this->assertNull(ResponseCookie::fromHeader("=123438afes7a8"));
    }

    public function testParsingOnInvalidNameValueCount()
    {
        $this->assertNull(ResponseCookie::fromHeader("; HttpOnly=123"));
    }

    public function testParsing()
    {
        // Examples from https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie
        $this->assertEquals(
            new ResponseCookie("sessionid", "38afes7a8", CookieAttributes::empty()->withHttpOnly()->withPath("/")),
            ResponseCookie::fromHeader("sessionid=38afes7a8; HttpOnly; Path=/")
        );

        $expectedMeta = CookieAttributes::empty()
            ->withHttpOnly()
            ->withSecure()
            ->withExpiry(new \DateTimeImmutable("Wed, 21 Oct 2015 07:28:00", new \DateTimeZone("GMT")));

        $this->assertEquals(
            new ResponseCookie("id", "a3fWa", $expectedMeta),
            ResponseCookie::fromHeader("id=a3fWa; Expires=Wed, 21 Oct 2015 07:28:00 GMT; Secure; HttpOnly")
        );

        // This might fail if the second switches between withMaxAge() and fromHeader() - we take the risk
        $expectedMeta = CookieAttributes::empty()
            ->withMaxAge(60);

        $this->assertEquals(
            new ResponseCookie("id", "a3fWa", $expectedMeta),
            ResponseCookie::fromHeader("id=a3fWa; Max-AGE=60")
        );

        // Missing "Wed, " in date, so date is ignored
        $expectedMeta = CookieAttributes::empty()
            ->withDomain("example.com")
            ->withPath("/");

        $this->assertEquals(
            new ResponseCookie("qwerty", "219ffwef9w0f", $expectedMeta),
            ResponseCookie::fromHeader("qwerty=219ffwef9w0f; Domain=example.com; Path=/; Expires=30 Aug 2019 00:00:00 GMT")
        );

        $expectedMeta = CookieAttributes::empty()
            ->withDomain("example.com")
            ->withPath("/")
            ->withExpiry(new \DateTimeImmutable("Wed, 30 Aug 2019 00:00:00", new \DateTimeZone("GMT")));

        $this->assertEquals(
            new ResponseCookie("qwerty", "219ffwef9w0f", $expectedMeta),
            $cookie = ResponseCookie::fromHeader("qwerty=219ffwef9w0f; Domain=example.com; Path=/; Expires=Wed, 30 Aug 2019 00:00:00 GMT")
        );

        $this->assertFalse($cookie->isSecure());
        $this->assertFalse($cookie->isHttpOnly());
        $this->assertSame("qwerty", $cookie->getName());
        $this->assertSame("219ffwef9w0f", $cookie->getValue());
        $this->assertSame("example.com", $cookie->getDomain());
        $this->assertSame("/", $cookie->getPath());
        $this->assertSame(
            (new \DateTimeImmutable("Wed, 30 Aug 2019 00:00:00", new \DateTimeZone("GMT")))->getTimestamp(),
            $cookie->getExpiry()->getTimestamp()
        );

        // Non-digit in Max-Age
        $this->assertEquals(
            new ResponseCookie("qwerty", "219ffwef9w0f", CookieAttributes::empty()),
            ResponseCookie::fromHeader("qwerty=219ffwef9w0f; Max-Age=12520b")
        );

        // "-" in front in Max-Age
        $this->assertEquals(
            new ResponseCookie("qwerty", "219ffwef9w0f", CookieAttributes::empty()->withMaxAge(-1)),
            ResponseCookie::fromHeader("qwerty=219ffwef9w0f; Max-Age=-1")
        );

        $this->assertNull(
            ResponseCookie::fromHeader("query foo=129")
        );
    }

    public function testGetMaxAge()
    {
        $responseCookie = new ResponseCookie("qwerty", "219ffwef9w0f", CookieAttributes::empty()->withMaxAge(10));
        $this->assertSame(10, $responseCookie->getMaxAge());
    }

    public function testInvalidName()
    {
        $this->expectException(InvalidCookieException::class);

        new ResponseCookie("foo:bar");
    }

    public function testInvalidValue()
    {
        $this->expectException(InvalidCookieException::class);

        new ResponseCookie("foobar", "foo;bar");
    }

    public function testGetAttributes()
    {
        $attributes = CookieAttributes::default();
        $cookie = new ResponseCookie("foobar", "xxx", $attributes);

        $this->assertSame($attributes, $cookie->getAttributes());
    }

    public function testToString()
    {
        $attributes = CookieAttributes::default();
        $cookie = new ResponseCookie("foobar", "xxx", $attributes);

        $this->assertSame("foobar=xxx; HttpOnly", (string) $cookie);
    }
}
