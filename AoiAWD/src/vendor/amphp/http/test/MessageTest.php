<?php

namespace Amp\Http\Test;

use Amp\Http\Message;
use PHPUnit\Framework\TestCase;

class TestMessage extends Message
{
    public function __construct(array $headers = [])
    {
        $this->setHeaders($headers);
    }

    public function setHeaders(array $headers)
    {
        parent::setHeaders($headers);
    }

    public function setHeader(string $name, $value)
    {
        parent::setHeader($name, $value);
    }

    public function addHeader(string $name, $value)
    {
        parent::addHeader($name, $value);
    }

    public function removeHeader(string $name)
    {
        parent::removeHeader($name);
    }
}

class MessageTest extends TestCase
{
    public function testGetHeader()
    {
        $message = new TestMessage([
            'foo' => 'bar',
        ]);

        $this->assertTrue($message->hasHeader('foo'));
        $this->assertSame(['foo' => ['bar']], $message->getHeaders());
        $this->assertSame('bar', $message->getHeader('foo'));
        $this->assertSame('bar', $message->getHeader('FOO'));
        $this->assertSame('bar', $message->getHeader('FoO'));
        $this->assertNull($message->getHeader('bar'));

        $this->assertSame(['bar'], $message->getHeaderArray('foo'));
        $this->assertSame([], $message->getHeaderArray('bar'));
    }

    public function testAddHeader()
    {
        $message = new TestMessage([
            'foo' => 'bar',
        ]);

        $this->assertSame(['bar'], $message->getHeaderArray('foo'));

        $message->addHeader('foo', 'bar');
        $this->assertSame(['bar', 'bar'], $message->getHeaderArray('foo'));

        $message->addHeader('bar', 'bar');
        $this->assertSame(['bar'], $message->getHeaderArray('bar'));

        $message->addHeader('bar', ['baz']);
        $this->assertSame(['bar', 'baz'], $message->getHeaderArray('bar'));

        $message->addHeader('bar', []);
        $this->assertSame(['bar', 'baz'], $message->getHeaderArray('bar'));
    }

    public function testSetHeader()
    {
        $message = new TestMessage([
            'foo' => 'bar',
        ]);

        $this->assertSame(['bar'], $message->getHeaderArray('foo'));

        $message->setHeader('foo', 'bar');
        $this->assertSame(['bar'], $message->getHeaderArray('foo'));

        $message->setHeader('bar', 'bar');
        $this->assertSame(['bar'], $message->getHeaderArray('bar'));

        $message->setHeaders(['bar' => []]);
        $this->assertSame(['bar'], $message->getHeaderArray('foo'));
        $this->assertFalse($message->hasHeader('bar'));
        $this->assertSame([], $message->getHeaderArray('bar'));

        $message->setHeader('bar', ['biz', 'baz']);
        $this->assertSame(['biz', 'baz'], $message->getHeaderArray('bar'));
        $this->assertSame('biz', $message->getHeader('bar'));
    }

    public function testInvalidName()
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Invalid header name');

        $message = new TestMessage;
        $message->setHeader("te\0st", 'value');
    }

    public function testInvalidValue()
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Invalid header value');

        $message = new TestMessage;
        $message->setHeader('foo', "te\0st");
    }
}
