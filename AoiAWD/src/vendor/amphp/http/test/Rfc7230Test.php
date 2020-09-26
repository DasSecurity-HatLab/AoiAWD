<?php

namespace Amp\Http;

use PHPUnit\Framework\TestCase;

class Rfc7230Test extends TestCase
{
    /** @dataProvider provideValidHeaders */
    public function testValidHeaderParsing(string $rawHeaders, array $expectedResult)
    {
        $result = Rfc7230::parseHeaders($rawHeaders);
        $this->assertSame($result, $expectedResult);
    }

    /** @dataProvider provideValidHeaders */
    public function testValidHeaderFormatting(string $rawHeaders /* ignored for this case */, array $expectedResult)
    {
        $result = Rfc7230::parseHeaders(Rfc7230::formatHeaders($expectedResult));
        $this->assertSame($result, $expectedResult);
    }

    public function provideValidHeaders()
    {
        return [
            ["x:y\r\n", ["x" => ["y"]]],
            ["server:\tamphp.org\r\n", ["server" => ["amphp.org"]]],
            ["server: \tamphp.org  \t \r\n", ["server" => ["amphp.org"]]],
            ["server: \tamphp.org  \t \r\nServer: amphp.org\r\n", ["server" => ["amphp.org", "amphp.org"]]],
            ["ser124ver:\tamphp.org\r\n", ["ser124ver" => ["amphp.org"]]],
        ];
    }

    /** @dataProvider provideInvalidHeaders */
    public function testInvalidHeaderParsing(string $rawHeaders)
    {
        $this->expectException(InvalidHeaderException::class);
        Rfc7230::parseHeaders($rawHeaders);
    }

    public function provideInvalidHeaders()
    {
        return [
            [" x:y\r\n"],
            ["x :z\r\n"],
            [" x:z\r\n"],
            ["x :z\nfoo: bar\r\n"],
            ["x:z\nfoo: bar\r\n"],
            ["ser ver:\tamphp.org\r\n"],
            ["server:\tamphp.org\r\n fold\r\n"],
        ];
    }

    public function testIgnoresHttp2PseudoHeaders()
    {
        $headers = [
            "foobar" => ["bar"],
            ":method" => ["GET"],
            "x" => ["y"],
        ];

        $this->assertSame("foobar: bar\r\nx: y\r\n", Rfc7230::formatHeaders($headers));
    }

    public function testDetectsHeaderInjectionsWithLfInValue()
    {
        $this->expectException(InvalidHeaderException::class);
        $this->expectExceptionMessage("Invalid headers");

        Rfc7230::formatHeaders(["foobar" => ["test\nbar"]]);
    }

    public function testDetectsHeaderInjectionsWithCrInValue()
    {
        $this->expectException(InvalidHeaderException::class);
        $this->expectExceptionMessage("Invalid headers");

        Rfc7230::formatHeaders(["foobar" => ["test\rbar"]]);
    }

    public function testDetectsHeaderInjectionsWithCrLfInValue()
    {
        $this->expectException(InvalidHeaderException::class);
        $this->expectExceptionMessage("Invalid headers");

        Rfc7230::formatHeaders(["foobar" => ["test\r\nbar"]]);
    }

    public function testDetectsHeaderInjectionsWithCrLfAndColonInValue()
    {
        $this->expectException(InvalidHeaderException::class);
        $this->expectExceptionMessage("Invalid headers");

        Rfc7230::formatHeaders(["foobar" => ["test\r\nfoo: bar"]]);
    }

    public function testDetectsHeaderInjectionsWithCrInName()
    {
        $this->expectException(InvalidHeaderException::class);
        $this->expectExceptionMessage("Invalid headers");

        Rfc7230::formatHeaders(["foobar\rfoobar" => ["bar"]]);
    }

    public function testDetectsHeaderInjectionsWithLfInName()
    {
        $this->expectException(InvalidHeaderException::class);
        $this->expectExceptionMessage("Invalid headers");

        Rfc7230::formatHeaders(["foobar\nfoobar" => ["bar"]]);
    }

    public function testDetectsHeaderInjectionsWithCrLfInName()
    {
        $this->expectException(InvalidHeaderException::class);
        $this->expectExceptionMessage("Invalid headers");

        Rfc7230::formatHeaders(["foobar\r\nfoobar" => ["bar"]]);
    }

    public function testDetectsHeaderInjectionsWithCrLfAndColonInName()
    {
        $this->expectException(InvalidHeaderException::class);
        $this->expectExceptionMessage("Invalid headers");

        Rfc7230::formatHeaders(["foobar: test\r\nfoobar" => ["bar"]]);
    }

    public function testDetectsInvalidHeaderSyntax()
    {
        $this->expectException(InvalidHeaderException::class);
        $this->expectExceptionMessage("Invalid headers");

        Rfc7230::formatHeaders(["foo bar" => ["bar"]]);
    }
}
