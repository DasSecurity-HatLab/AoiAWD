---
title: IteratorStream
permalink: /iterator-stream
---
`IteratorStream` allows converting an `Amp\Iterator` that yields strings into an `InputStream`.

```php
$inputStream = new IteratorStream(new Producer(function (callable $emit) {
    for ($i = 0; $i < 10; $i++) {
        yield new Delayed(1000);
        yield $emit(".");
    }
});
```
