---
title: Client
permalink: /client
---
`amphp/socket` allows clients to connect to servers via TCP, UDP, or Unix domain sockets.

## Connecting

You can establish a socket connection to a specified URI by using `Amp\Socket\connect`. It will automatically take care of resolving DNS names and will try other IPs if a connection fails and multiple IPs are available via DNS.

```php
/**
 * Asynchronously establish a socket connection to the specified URI.
 *
 * @param string                 $uri URI in scheme://host:port format. TCP is assumed if no scheme is present.
 * @param ClientConnectContext   $socketContext Socket connect context to use when connecting.
 * @param CancellationToken|null $token
 *
 * @return Promise<\Amp\Socket\ClientSocket>
 */
function connect(
    string $uri,
    ClientConnectContext $socketContext = null,
    CancellationToken $token = null
): Promise {
    /* ... */
}
```

### TLS

If you want to connect via TLS, you can use `Amp\Socket\cryptoConnect()`, which connects to the specified URI and enables TLS in one step.

```php
/**
 * Asynchronously establish an encrypted TCP connection (non-blocking).
 *
 * Note: Once resolved the socket stream will already be set to non-blocking mode.
 *
 * @param string               $uri
 * @param ClientConnectContext $socketContext
 * @param ClientTlsContext     $tlsContext
 * @param CancellationToken    $token
 *
 * @return Promise<ClientSocket>
 */
function cryptoConnect(
    string $uri,
    ClientConnectContext $socketContext = null,
    ClientTlsContext $tlsContext = null,
    CancellationToken $token = null
): Promise {
    /* ... */
}
```

{:.note}
> If you want to connect and enable TLS at a later time, you can use `Socket::enableCrypto()` on the `Socket` instance returned from `connect()`.

## Sending Data

`ClientSocket` implements `OutputStream`, so everything from [`amphp/byte-stream`](https://amphp.org/byte-stream/#outputstream) applies.

## Receiving Data

`ClientSocket` implements `InputStream`, so everything from [`amphp/byte-stream`](https://amphp.org/byte-stream/#inputstream) applies.
