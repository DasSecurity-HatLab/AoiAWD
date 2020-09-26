<?php

require __DIR__ . '/../vendor/autoload.php';

use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Router;
use Amp\Http\Server\Server;
use Amp\Http\Server\Response;
use Amp\Http\Server\StaticContent\DocumentRoot;
use Amp\Http\Status;
use Amp\Socket;
use Psr\Log\NullLogger;

// Run this script, then visit http://localhost:1337/ in your browser.

Amp\Loop::run(function () {
    $sockets = [
        Socket\listen("0.0.0.0:1337"),
        Socket\listen("[::]:1337"),
    ];

    $documentRoot = new DocumentRoot(__DIR__ . '/public');

    $router = new Router;
    $router->setFallback($documentRoot);
    $router->addRoute('GET', '/', new CallableRequestHandler(function () {
        // This can also be in a index.htm file, but we want a demo that uses the router.
        $html = <<<HTML
<!DOCTYPE html>
<html>
    <head>
        <title>Example</title>
        <link rel="stylesheet" href="/style.css"/>
    </head>
    
    <body>
        <div>
            Hello, World!
        </div>
    </body>
</html>
HTML;

        return new Response(Status::OK, ['content-type' => 'text/html; charset=utf-8'], $html);
    }));

    $server = new Server($sockets, $router, new NullLogger);

    yield $server->start();

    // Stop the server gracefully when SIGINT is received.
    // This is technically optional, but it is best to call Server::stop().
    Amp\Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
        Amp\Loop::cancel($watcherId);
        yield $server->stop();
    });
});
