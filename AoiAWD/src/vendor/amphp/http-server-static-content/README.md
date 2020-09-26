# http-server-static-content

This package provides a static content `RequestHandler` for [Amp's HTTP server](https://github.com/amphp/http-server).

## Usage

**`DocumentRoot`** implements `RequestHandler`.

## Example

```php
<?php

use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Amp\Http\Server\Server;
use Amp\Http\Server\StaticContent\DocumentRoot;
use Amp\Http\Status;

$documentRoot = new DocumentRoot(__DIR__ . '/public');

$router = new Amp\Http\Server\Router;

$router->addRoute('GET', '/', new CallableRequestHandler(function () {
    return new Response(Status::OK, ['content-type' => 'text/plain'], 'Hello, world!');
}));

$router->setFallback($documentRoot);

$server = new Server(..., $router, ...);
```