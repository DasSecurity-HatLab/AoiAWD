---
title: Payload
permalink: /payload
---
`Payload` implements `InputStream` while also providing a method `buffer()` for buffering the entire contents. This allows consuming a message either in chunks (streaming) or consume everything at once (buffering). When the object is destructed, any remaining data in the stream is automatically consumed and discarded. This class is useful for small payloads or when the entire contents of a stream is needed before any processing can be done.

## Buffering

Buffering a complete input stream is quite easy, you can simply `yield` the promise returned from `buffer()` just like any other `Promise`. If you have an `InputStream` that's not a `Payload`, simply create a new instance from it using `new Payload($inputStream)`.

```php
$payload = new Payload($inputStream);
$content = yield $payload->buffer();
```

## Streaming

Sometimes it's useful / possible to consume a payload in chunks rather than first buffering it completely. An example might be streaming a large HTTP response body directly to disk.

```php
while (($chunk = yield $payload->read()) !== null) {
    // Use $chunk here, works just like any other InputStream
}
```
