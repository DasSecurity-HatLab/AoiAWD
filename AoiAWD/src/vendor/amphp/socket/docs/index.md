---
title: Socket Overview
permalink: /
---
`amphp/socket` provides a socket abstraction for clients and servers. It abstracts the really low levels of non-blocking streams in PHP.

## Client Example

```php
$uri = new Uri($argv[1]);
$host = $uri->getHost();

if ($uri->getScheme() === "https") {
    /** @var Socket $socket */
    $socket = yield cryptoConnect("tcp://" . $host . ":" . $uri->getPort());
} else {
    /** @var Socket $socket */
    $socket = yield connect("tcp://" . $host . ":" . $uri->getPort());
}

yield $socket->write("GET {$uri} HTTP/1.1\r\nHost: $host\r\nConnection: close\r\n\r\n");

while (null !== $chunk = yield $socket->read()) {
    print $chunk;
}
```

## Server Example

```php
Loop::run(function () {
    $clientHandler = function (ServerSocket $socket) {
        list($ip, $port) = explode(":", $socket->getRemoteAddress());
        
        echo "Accepted connection from {$ip}:{$port}." . PHP_EOL;
        
        $body = "Hey, your IP is {$ip} and your local port used is {$port}.";
        $bodyLength = \strlen($body);
        
        yield $socket->end("HTTP/1.1 200 OK\r\nConnection: close\r\nContent-Length: {$bodyLength}\r\n\r\n{$body}");
    };
    
    $server = Amp\Socket\listen("127.0.0.1:0");
    
    echo "Listening for new connections on " . $server->getAddress() . " ..." . PHP_EOL;
    echo "Open your browser and visit http://" . $server->getAddress() . "/" . PHP_EOL;
    
    while ($socket = yield $server->accept()) {
        Amp\asyncCall($clientHandler, $socket);
    }
});
```
