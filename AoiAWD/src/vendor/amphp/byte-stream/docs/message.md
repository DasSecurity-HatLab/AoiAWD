---
title: Message
permalink: /message
---

{:.note}
> `Message` has been deprecated. Use [`Payload`](./payload.md) instead.

`Message` implements both `InputStream` _and_ `Promise`. This allows consuming a message either in chunks (streaming) or consume everything at once (buffering).

## Buffering

Buffering a complete input stream is quite easy, you can simply `yield` a `Message` just like any other `Promise`. If you have an `InputStream` that's not a `Message`, simply create a new instance from it using `new Message($inputStream)`.

```php
$message = new Message($inputStream);
$content = yield $message;
```

## Streaming

Sometimes it's useful / possible to consume a message in chunks rather than first buffering it completely. An example might be streaming a large HTTP response body directly to disk.

```php
while (($chunk = yield $message->read()) !== null) {
    // Use $chunk here, works just like any other InputStream
}
```

## Unwrapping

In some cases you might want to resolve a promise with an `InputStream` or your method explicitly declares `InputStream` as a return type. In these cases you should use `Message::getInputStream` to return the raw input stream. This makes it possible to resolve promises with the `InputStream` and not run into unexpected issues. Only return a `Message` if you declare that as a type, otherwise an API assumes `InputStream` and might try to resolve a promise with that, resulting in buffering the message's content instead of resolving the promise with an `InputStream` instance.
