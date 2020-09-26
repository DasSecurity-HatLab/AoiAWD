---
title: Cookies
permalink: /cookies
---
HTTP cookies are specified by [RFC 6265](https://tools.ietf.org/html/rfc6265).
This package implements parsers for the `set-cookie` and `cookie` headers.
It further has a developer friendly API for creating such headers.

{:.note}
> This library doesn't set standards regarding the cookie encoding.
> As such, the limitations of RFC 6265 apply to names and values.
> If you need to set arbitrary values for certain cookies, it's recommended to use an encoding mechanism like URL encoding or Base64.

## Set-Cookie

The `set-cookie` header is used to create cookies.
Servers send this header in responses and clients parse the headers if a response contains such headers.
Every header contains exactly one header.
Hence, the responsible class is called `ResponseCookie`.

{:.note}
> More information about `set-cookie` can by obtained from the [MDN reference](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie) or other sources.

`ResponseCookie::fromHeader()` accepts a header value and attempts to parse it.
If the parsing succeeds, a `ResponseCookie` is returned.
If not, `null` is returned.
No exceptions are thrown, because received cookies are always user input and untrusted and malformed headers should be discarded according to the RFC.

```php
$attributes = CookieAttributes::default()->withSecure();
$cookie = new ResponseCookie("session", \bin2hex(\random_bytes(16)), $attributes);

var_dump($cookie->getName());
var_dump($cookie->getValue());
var_dump($cookie->isHttpOnly());
var_dump("set-cookie: " . $cookie);
```

```plain
string(7) "session"
string(32) "7b6f532a60bc0786fdfc42307649d634"
bool(true)
string(70) "set-cookie: session=7b6f532a60bc0786fdfc42307649d634; Secure; HttpOnly"
```

## Cookie

The `cookie` header is used to send cookies from a client to a server.
Clients send this header in requests and servers parse the header if a request contains such a header.
Clients must not send more than one such header.
Hence, the responsible class is called `RequestCookie`.

{:.note}
> More information about `cookie` can by obtained from the [MDN reference](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cookie) or other sources.

`RequestCookie::fromHeader()` accepts a header value and attempts to parse it.
If the parsing succeeds, an array of `RequestCookie` instances is returned.
If not, an empty array is returned.
No exceptions are thrown, because received cookies are always user input and untrusted and malformed headers should be discarded according to the RFC.

```php
$responseCookie = new ResponseCookie("session", \bin2hex(\random_bytes(16)), $attributes);

$cookie = ResponseCookie::fromHeader($responseCookie);
$cookie = new RequestCookie("session", $cookie->getValue());

var_dump($cookie->getName());
var_dump($cookie->getValue());
var_dump("cookie: " . $cookie);
```

```plain
string(7) "session"
string(32) "7b6f532a60bc0786fdfc42307649d634"
string(48) "cookie: session=7b6f532a60bc0786fdfc42307649d634"
```
