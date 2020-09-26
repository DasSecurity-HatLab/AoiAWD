<?php

namespace Amp\Http\Server;

use Amp\Failure;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Status;
use Amp\Promise;
use cash\LRUCache;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function Amp\call;
use function FastRoute\simpleDispatcher;

final class Router implements RequestHandler, ServerObserver {
    const DEFAULT_CACHE_SIZE = 512;

    /** @var bool */
    private $running = false;

    /** @var Dispatcher */
    private $routeDispatcher;

    /** @var ErrorHandler */
    private $errorHandler;

    /** @var RequestHandler|null */
    private $fallback;

    /** @var \SplObjectStorage */
    private $observers;

    /** @var array */
    private $routes = [];

    /** @var Middleware[] */
    private $middlewares = [];

    /** @var string */
    private $prefix = "/";

    /** @var LRUCache */
    private $cache;

    /**
     * @param int $cacheSize Maximum number of route matches to cache.
     *
     * @throws \Error If `$cacheSize` is less than zero.
     */
    public function __construct(int $cacheSize = self::DEFAULT_CACHE_SIZE) {
        if ($cacheSize <= 0) {
            throw new \Error("The number of cache entries must be greater than zero");
        }

        $this->cache = new LRUCache($cacheSize);
        $this->observers = new \SplObjectStorage;
    }

    /**
     * Route a request and dispatch it to the appropriate handler.
     *
     * @param Request $request
     *
     * @return Promise<\Amp\Http\Server\Response>
     */
    public function handleRequest(Request $request): Promise {
        $method = $request->getMethod();
        $path = \rawurldecode($request->getUri()->getPath());

        $toMatch = "{$method}\0{$path}";

        if (null === $match = $this->cache->get($toMatch)) {
            $match = $this->routeDispatcher->dispatch($method, $path);
            $this->cache->put($toMatch, $match);
        }

        switch ($match[0]) {
            case Dispatcher::FOUND:
                /**
                 * @var RequestHandler $requestHandler
                 * @var string[] $routeArgs
                 */
                list(, $requestHandler, $routeArgs) = $match;
                $request->setAttribute(self::class, $routeArgs);

                return $requestHandler->handleRequest($request);

            case Dispatcher::NOT_FOUND:
                if ($this->fallback !== null) {
                    return $this->fallback->handleRequest($request);
                }

                return $this->makeNotFoundResponse($request);

            case Dispatcher::METHOD_NOT_ALLOWED:
                return $this->makeMethodNotAllowedResponse($match[1], $request);

            default:
                // @codeCoverageIgnoreStart
                throw new \UnexpectedValueException(
                    "Encountered unexpected dispatcher code: " . $match[0]
                );
                // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Create a response if no routes matched and no fallback has been set.
     *
     * @param Request $request
     *
     * @return Promise<\Amp\Http\Server\Response>
     */
    private function makeNotFoundResponse(Request $request): Promise {
        return $this->errorHandler->handleError(Status::NOT_FOUND, null, $request);
    }

    /**
     * Create a response if the requested method is not allowed for the matched path.
     *
     * @param string[] $methods
     * @param Request $request
     *
     * @return Promise<\Amp\Http\Server\Response>
     */
    private function makeMethodNotAllowedResponse(array $methods, Request $request): Promise {
        return call(function () use ($methods, $request) {
            /** @var \Amp\Http\Server\Response $response */
            $response = yield $this->errorHandler->handleError(Status::METHOD_NOT_ALLOWED, null, $request);
            $response->setHeader("allow", \implode(", ", $methods));
            return $response;
        });
    }

    /**
     * Merge another router's routes into this router.
     *
     * Doing so might improve performance for request dispatching.
     *
     * @param self $router Router to merge.
     */
    public function merge(self $router) {
        if ($this->running) {
            throw new \Error("Cannot merge routers after the server has started");
        }

        foreach ($router->routes as $route) {
            $route[1] = \ltrim($router->prefix, "/") . $route[1];
            $route[2] = Middleware\stack($route[2], ...$router->middlewares);
            $this->routes[] = $route;
        }

        $this->observers->addAll($router->observers);
    }

    /**
     * Prefix all currently defined routes with a given prefix.
     *
     * If this method is called multiple times, the second prefix will be before the first prefix and so on.
     *
     * @param string $prefix Path segment to prefix, leading and trailing slashes will be normalized.
     */
    public function prefix(string $prefix) {
        if ($this->running) {
            throw new \Error("Cannot alter routes after the server has started");
        }

        $prefix = \trim($prefix, "/");

        if ($prefix !== "") {
            $this->prefix = "/" . $prefix . $this->prefix;
        }
    }

    /**
     * Define an application route.
     *
     * Matched URI route arguments are made available to request handlers as a request attribute
     * which may be accessed with:
     *
     *     $request->getAttribute(Router::class)
     *
     * Route URIs ending in "/?" (without the quotes) allow a URI match with or without
     * the trailing slash. Temporary redirects are used to redirect to the canonical URI
     * (with a trailing slash) to avoid search engine duplicate content penalties.
     *
     * @param string    $method The HTTP method verb for which this route applies.
     * @param string    $uri The string URI.
     * @param RequestHandler $requestHandler Request handler invoked on a route match.
     * @param Middleware[] ...$middlewares
     *
     * @throws \Error If the server has started, or if $method is empty.
     */
    public function addRoute(string $method, string $uri, RequestHandler $requestHandler, Middleware ...$middlewares) {
        if ($this->running) {
            throw new \Error(
                "Cannot add routes once the server has started"
            );
        }

        if ($method === "") {
            throw new \Error(
                __METHOD__ . "() requires a non-empty string HTTP method at Argument 1"
            );
        }

        if ($requestHandler instanceof ServerObserver) {
            $this->observers->attach($requestHandler);
        }

        if (!empty($middlewares)) {
            foreach ($middlewares as $middleware) {
                if ($middleware instanceof ServerObserver) {
                    $this->observers->attach($middleware);
                }
            }

            $requestHandler = Middleware\stack($requestHandler, ...$middlewares);
        }

        $this->routes[] = [$method, \ltrim($uri, "/"), $requestHandler];
    }

    /**
     * Specifies a set of middlewares that is applied to every route, but will not be applied to the fallback request
     * handler.
     *
     * All middlewares are called in the order they're passed, so the first middleware is the outer middleware.
     *
     * On repeated calls, the later call will wrap the passed middlewares around the previous stack. This ensures a
     * router can use `stack()` and then another entity can wrap a router with additional middlewares.
     *
     * @param Middleware[] ...$middlewares
     *
     * @throws \Error If the server has started.
     */
    public function stack(Middleware ...$middlewares) {
        if ($this->running) {
            throw new \Error("Cannot set middlewares after the server has started");
        }

        $this->middlewares = array_merge($middlewares, $this->middlewares);
    }

    /**
     * Specifies an instance of RequestHandler that is used if no routes match.
     *
     * If no fallback is given, a 404 response is returned from `respond()` when no matching routes are found.
     *
     * @param RequestHandler $requestHandler
     *
     * @throws \Error If the server has started.
     */
    public function setFallback(RequestHandler $requestHandler) {
        if ($this->running) {
            throw new \Error("Cannot add fallback request handler after the server has started");
        }

        $this->fallback = $requestHandler;
    }

    public function onStart(Server $server): Promise {
        if ($this->running) {
            return new Failure(new \Error("Router already started"));
        }

        if (empty($this->routes)) {
            return new Failure(new \Error(
                "Router start failure: no routes registered"
            ));
        }

        $this->running = true;

        $options = $server->getOptions();
        $allowedMethods = $options->getAllowedMethods();
        $logger = $server->getLogger();

        $this->routeDispatcher = simpleDispatcher(function (RouteCollector $rc) use ($allowedMethods, $logger) {
            foreach ($this->routes as list($method, $uri, $requestHandler)) {
                if (!\in_array($method, $allowedMethods, true)) {
                    $logger->alert(
                        "Router URI '$uri' uses method '$method' that is not in the list of allowed methods"
                    );
                }

                $requestHandler = Middleware\stack($requestHandler, ...$this->middlewares);
                $uri = $this->prefix . $uri;

                // Special-case, otherwise we redirect just to the same URI again
                if ($uri === "/?") {
                    $uri = "/";
                }

                if (substr($uri, -2) === "/?") {
                    $canonicalUri = \substr($uri, 0, -2);
                    $redirectUri = \substr($uri, 0, -1);

                    $rc->addRoute($method, $canonicalUri, $requestHandler);
                    $rc->addRoute($method, $redirectUri, new CallableRequestHandler(static function (Request $request): Response {
                        $uri = $request->getUri();
                        $path = \rtrim($uri->getPath(), '/');

                        if ($uri->getQuery() !== "") {
                            $redirectTo = $path . "?" . $uri->getQuery();
                        } else {
                            $redirectTo = $path;
                        }

                        return new Response(Status::PERMANENT_REDIRECT, [
                            "location" => $redirectTo,
                            "content-type" => "text/plain; charset=utf-8",
                        ], "Canonical resource location: {$path}");
                    }));
                } else {
                    $rc->addRoute($method, $uri, $requestHandler);
                }
            }
        });

        $this->errorHandler = $server->getErrorHandler();

        if ($this->fallback instanceof ServerObserver) {
            $this->observers->attach($this->fallback);
        }

        foreach ($this->middlewares as $middleware) {
            if ($middleware instanceof ServerObserver) {
                $this->observers->attach($middleware);
            }
        }

        $promises = [];
        foreach ($this->observers as $observer) {
            $promises[] = $observer->onStart($server);
        }

        return Promise\all($promises);
    }

    public function onStop(Server $server): Promise {
        $this->routeDispatcher = null;
        $this->running = false;

        $promises = [];
        foreach ($this->observers as $observer) {
            $promises[] = $observer->onStop($server);
        }

        return Promise\all($promises);
    }
}
