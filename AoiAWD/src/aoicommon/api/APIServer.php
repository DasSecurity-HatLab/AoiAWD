<?php
namespace aoicommon\api;

use Amp\Http\Server\Server as HTTPServer;
use Amp\Http\Server\Router;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use function Amp\Socket\listen;

use Amp\Http\Status;
use Amp\Http\Server\Response;
use aoicommon\api\BaseAPIController;
use aoicommon\helper\CommonHelper;

class APIServer
{
    const API_METHOD = ['GET', 'POST'];
    private $httpListen;
    private $httpRouter;
    private $httpServer;
    private $api;

    public function __construct($uri)
    {
        $this->httpListen = [listen($uri)];
        $this->api = new \stdClass;
        $this->httpRouter = new Router();
    }

    public function start()
    {
        $this->httpRouter->addRoute('GET', '/api/ping', new CallableRequestHandler([$this, "actionPing"]));
        foreach ($this->api as $key => $value) {
            $this->httpRouter->addRoute('OPTIONS', "/api/{$key}/{action}", new CallableRequestHandler([$this, "actionOptions"]));
            foreach (self::API_METHOD as $method) {
                $this->httpRouter->addRoute($method, "/api/{$key}/{action}", $value);
            }
        }
        $this->httpServer = new HTTPServer($this->httpListen, $this->httpRouter, CommonHelper::getLogger(HTTPServer::class));
        return $this->httpServer->start();
    }

    public function actionPing()
    {
        return new Response(Status::OK, [], "pong");
    }
    
    public function actionOptions()
    {
        $header = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => '*'
        ];
        return new Response(Status::OK, $header);
    }

    public function addHandler($name, BaseAPIController $instance)
    {
        $this->api->$name = $instance;
    }

    public function getRouter()
    {
        return $this->httpRouter;
    }
}
