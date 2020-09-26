---
title: InMemoryStream
permalink: /in-memory-stream
---
An `InMemoryStream` allows creating an `InputStream` from a single known string chunk. This is helpful if the complete stream contents are already known.

```php
$inputStream = new InMemoryStream("foobar");
```

It also allows creating a stream without any chunks by passing `null` as chunk.

```php
$inputStream = new InMemoryStream;

// The stream ends immediately
assert(null === yield $inputStream->read());
```
