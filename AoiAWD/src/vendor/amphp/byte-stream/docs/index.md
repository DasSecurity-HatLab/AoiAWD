---
title: Overview
permalink: /
---
Streams are an abstraction over ordered sequences of bytes. This package provides the fundamental interfaces `InputStream` and `OutputStream`.

## InputStream

`InputStream` offers a single method: `read()`. It returns a promise that gets either resolved with a `string` or `null`. `null` indicates that the stream has ended.

### Example

This example shows a simple `InputStream` consumption that buffers the complete stream contents inside a coroutine.

```php
$inputStream = ...;
$buffer = "";

while (($chunk = yield $inputStream->read()) !== null) {
    $buffer .= $chunk;
}

// do something with $buffer
```

{:.note}
> While buffering a stream that way is relatively straightforward, you might want to use `yield new Message($inputStream)` to buffer a complete `InputStream`, making it even easier.

### Implementations

This package offers some basic implementations, other libraries might provide even more implementations, such as [`amphp/socket`](https://github.com/amphp/socket).

 * [`InMemoryStream`](./in-memory-stream.md)
 * [`IteratorStream`](./iterator-stream.md)
 * [`Message`](./message.md)
 * [`Payload`](./payload.md)
 * [`ResourceInputStream`](./resource-streams.md)
 * [`ZlibInputStream`](./compression-streams.md)

## OutputStream

`OutputStream` offers two methods: `write()` and `end()`.

### `write()`

`write()` writes the given string to the stream. The returned `Promise` might be used to wait for completion. Waiting for completion allows writing only as fast as the underlying stream can write and potentially send over a network. TCP streams will resolve the returned `Promise` immediately as long as the write buffer isn't full.

The write order is always ensured, even if the writer doesn't wait on the promise.

{:.note}
> Use `Amp\Promise\rethrow` on the returned `Promise` if you do not wait on it to get notified about write errors instead of silently doing nothing on errors.

### `end()`

`end()` marks the stream as ended, optionally writing a last data chunk before. TCP streams might close the underlying stream for writing, but MUST NOT close it. Instead, all resources should be freed and actual resource handles be closed by PHP's garbage collection process.

## Example

This example uses the previous example to read from a stream and simply writes all data to an `OutputStream`.

```php
$inputStream = ...;
$outputStream = ...;
$buffer = "";

while (($chunk = yield $inputStream->read()) !== null) {
    yield $outputStream->write($chunk);
}

yield $outputStream->end();
```

### Implementations

This package offers some basic implementations, other libraries might provide even more implementations, such as [`amphp/socket`](https://github.com/amphp/socket).

 * [`ResourceOutputStream`](./resource-streams.md)
 * [`ZlibOutputStream`](./compression-streams.md)
