# http-server-router

This package provides a routing `RequestHandler` for [Amp's HTTP server](https://github.com/amphp/http-server) based on the request URI and method based on [FastRoute](https://github.com/nikic/FastRoute).

## Usage

**`Router`** implements `RequestHandler`. Any attached `RequestHandler` and `Middleware` instances will receive any `ServerObserver` events.

Routes can be defined using the `addRoute($method, $uri, $requestHandler)` method. Please read the [FastRoute documentation on how to define placeholders](https://github.com/nikic/FastRoute#defining-routes).

Matched route arguments are available in the request attributes under the `Amp\Http\Server\Router` key as an associative array.

## Example

```php
$router = new Router;

$router->addRoute('GET', '/', new CallableRequestHandler(function () {
    return new Response(Status::OK, ['content-type' => 'text/plain'], 'Hello, world!');
}));

$router->addRoute('GET', '/{name}', new CallableRequestHandler(function (Request $request) {
    $args = $request->getAttribute(Router::class);
    return new Response(Status::OK, ['content-type' => 'text/plain'], "Hello, {$args['name']}!");
}));
```

## Limitations

The `Router` will decode the URI path before matching.
This will also decode any forward slashes (`/`), which might result in unexpected matching for URIs with encoded slashes.
FastRoute placeholders match path segments by default, which are separated by slashes.
That means a route like `/token/{token}` won't match if the token contains an encoded slash.
You can work around this limitation by using a custom regular expression for the placeholder like `/token/{token:.+}`.