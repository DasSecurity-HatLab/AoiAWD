---
title: Headers
permalink: /headers
---
This package provides an HTTP header parser based on [RFC 7230](https://tools.ietf.org/html/rfc7230).
It also provides a corresponding header formatter.

## Parsing Headers

`Amp\Http\Rfc7230::parseHeaders()` parses raw headers into an array mapping header names to arrays of header values.
Every header line must end with `\r\n`, also the last one.

```php
<?php

use Amp\Http\Rfc7230;

require __DIR__ . "/../vendor/autoload.php";

$rawHeaders = "Server: GitHub.com\r\n"
    . "Date: Tue, 31 Oct 2006 08:00:29 GMT\r\n"
    . "Connection: close\r\n"
    . "Content-Length: 0\r\n";

$headers = Rfc7230::parseHeaders($rawHeaders);

var_dump($headers);
```

```plain
array(4) {
  ["server"]=>
  array(1) {
    [0]=>
    string(10) "GitHub.com"
  }
  ["date"]=>
  array(1) {
    [0]=>
    string(29) "Tue, 31 Oct 2006 08:00:29 GMT"
  }
  ["connection"]=>
  array(1) {
    [0]=>
    string(5) "close"
  }
  ["content-length"]=>
  array(1) {
    [0]=>
    string(1) "0"
  }
}
```

## Formatting Headers

`Amp\Http\Rfc7230::formatHeaders()` takes an array with the same format as `parseHeaders()` returns.
It protects against header injections and other non-compliant header names and values.

```php
<?php

use Amp\Http\Cookie\ResponseCookie;
use Amp\Http\Rfc7230;

require __DIR__ . "/../vendor/autoload.php";

$headers = Rfc7230::formatHeaders([
    "server" => [
        "GitHub.com",
    ],
    "location" => [
        "https://github.com/",
    ],
    "set-cookie" => [
        new ResponseCookie("session", \bin2hex(\random_bytes(16))),
        new ResponseCookie("user", "amphp"),
    ]
]);

var_dump($headers);
```

```plain
string(149) "server: GitHub.com
location: https://github.com/
set-cookie: session=09f1906ab952c9ae14e2c07bb714392f; HttpOnly
set-cookie: user=amphp; HttpOnly
"
```
