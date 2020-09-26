<?php
namespace aoicommon\api;

use Amp\Http\Server\RequestHandler;
use Amp\Promise;
use Amp\Http\Server\Request;
use Amp\Http\Server\Router;
use function Amp\call;
use Amp\Http\Server\Response;
use Amp\Http\Status;

abstract class BaseAPIController implements RequestHandler
{
    protected $request;
    protected $_accessToken;
    protected $_GET;
    protected $_POST;

    protected function response($status, $data = null)
    {
        $header = [
            'Access-Control-Allow-Origin' => '*',
            'Content-Type' => 'application/json'
        ];
        return new Response($status, $header, json_encode($data));
    }

    public function actionReject()
    {
        $reply = new DataStruct;
        $reply->status = false;
        $reply->message = "access forbidden";
        return $this->response(Status::FORBIDDEN, $reply);
    }

    public function actionDefault()
    {
        $reply = new DataStruct;
        $reply->status = false;
        $reply->message = "method not found";
        return $this->response(Status::NOT_FOUND, $reply);
    }

    public function setAccessToken($token)
    {
        $this->_accessToken = $token;
    }

    public function handleRequest(Request $request): Promise
    {
        return call(function () use ($request) {
            $requestBody = yield $request->getBody()->buffer();
            $this->request = $request;
            $this->_GET = new \stdClass;
            $queries = $this->request->getUri()->getQuery();
            $queries = explode('&', $queries);
            foreach ($queries as $query) {
                $query = explode('=', $query);
                if (isset($query[1])) {
                    $key = urldecode($query[0]);
                    $value = urldecode($query[1]);
                    $this->_GET->$key = $value;
                }
            }
            if ($this->request->getMethod() == "POST") {
                $contentType = $request->getHeader("content-type");
                $allowedType = "application/json";
                if (strncmp($contentType, $allowedType, \strlen($allowedType)) === 0) {
                    $this->_POST = json_decode($requestBody);
                }
            }
            $token = $this->_GET->token ?? $request->getHeader('token');
            if ($this->_accessToken != null && $token != $this->_accessToken) {
                return call([$this, "actionReject"]);
            }
            $requestArgs = $request->getAttribute(Router::class);
            $requestAction = $requestArgs['action'] ?? 'Default';
            $requestAction = "action{$requestAction}";
            if (method_exists($this, $requestAction)) {
                return call([$this, $requestAction]);
            } else {
                return call([$this, "actionDefault"]);
            }
        });
    }
}
