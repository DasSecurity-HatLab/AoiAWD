<?php

namespace Amp\Http\Cookie;

use PHPUnit\Framework\TestCase;

class RequestCookieTest extends TestCase
{
    public function testParsing()
    {
        $this->assertEquals([new RequestCookie("foobar", "xxx")], RequestCookie::fromHeader("foobar=xxx"));
        $this->assertEquals([new RequestCookie("foobar", "x%20x")], RequestCookie::fromHeader("foobar=x%20x"));
        $this->assertEquals([new RequestCookie("a", "1"), new RequestCookie("b", "2")], RequestCookie::fromHeader("a=1;b=2"));
        $this->assertEquals([new RequestCookie("a", "1"), new RequestCookie("b", "2")], RequestCookie::fromHeader("a=1; b=2"));
        $this->assertEquals([new RequestCookie("a", "1"), new RequestCookie("b", "2")], RequestCookie::fromHeader("a=1 ;b=2"));
        $this->assertEquals([new RequestCookie("a", "1"), new RequestCookie("b", "-2")], RequestCookie::fromHeader("a=1; b = -2"));
        $this->assertSame([], RequestCookie::fromHeader("a=1; b=2 2"));

        // Any missing = MUST discard the full cookie header
        $this->assertSame([], RequestCookie::fromHeader("a=1; b"));
    }

    public function testInvalidCookieName()
    {
        $this->expectException(InvalidCookieException::class);

        new RequestCookie("foo bar");
    }

    public function testInvalidCookieValue()
    {
        $this->expectException(InvalidCookieException::class);

        new RequestCookie("foobar", "what is this");
    }

    public function testGetters()
    {
        $cookie = new RequestCookie("foobar", "baz");

        $this->assertSame("foobar", $cookie->getName());
        $this->assertSame("baz", $cookie->getValue());
        $this->assertSame("foobar=baz", (string) $cookie);
    }
}
