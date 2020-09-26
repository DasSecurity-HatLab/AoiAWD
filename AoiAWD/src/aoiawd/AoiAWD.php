<?php
namespace aoiawd;

use Amp\Loop;

use aoiawd\plugin\PluginManager;
use Amp\Http\Server\StaticContent\DocumentRoot;
use Amp\File\BlockingDriver;
use MongoDB\Client as MongoClient;
use MongoDB\Database;
use aoicommon\helper\CommonHelper;
use aoicommon\socket\AsyncTCPServer;
use aoicommon\api\APIServer;
use aoiawd\api\v1;

class AoiAWD
{
    static private $_self_instance;

    private $config = [
        'httpServer' => "",
        'logServer' => "",
        'mongoDB' => ""
    ];

    private $logger;

    /** @var LogReceiver */
    private $logServer;

    /** @var APIServer */
    private $apiServer;

    /** @var Database */
    private $mongoDB;

    /** @var PluginManager */
    private $pluginManager;

    private $startTime;

    private $alertCount;

    private $lastUpdate;

    private $accessToken;

    private $databaseName;

    public function __construct()
    {
        self::$_self_instance = $this;
        $this->alertCount = 0;
        $this->startTime = time();
        $this->lastUpdate = time();
        $this->getArgv();
        $this->logger = CommonHelper::getLogger('MainServer');
        $this->logger->notice("AccessToken: {$this->accessToken}");
        $this->mongoDB = (new MongoClient($this->config['mongoDB']))->{$this->databaseName};
        $this->logger->info("MongoDB Connect {$this->config['mongoDB']}");
        $this->initAPIServer();
        $this->logServer = new LogReceiver(new AsyncTCPServer($this->config['logServer']));
        $this->pluginManager = new PluginManager($this, CommonHelper::getLogger('PluginManager'));
        Loop::run([$this, "run"]);
    }

    public function initAPIServer()
    {
        $this->apiServer = new APIServer($this->config['httpServer']);
        $this->apiServer->getRouter()->addRoute('GET', '/websocket', new WebsocketHandler);
        $this->apiServer->getRouter()->setFallback(new DocumentRoot(PHAR_BASE . '/public', new BlockingDriver));
        $apiV1 = new v1;
        $apiV1->setAccessToken($this->accessToken);
        $this->apiServer->addHandler('v1', $apiV1);
    }

    public function getArgv()
    {
        $option = getopt('w:l:m:t:h');
        $this->config['httpServer'] = $option['w'] ?? "tcp://0.0.0.0:1337";
        $this->config['logServer'] = $option['l'] ?? "tcp://0.0.0.0:8023";
        $this->config['mongoDB'] = $option['m'] ?? "mongodb://127.0.0.1:27017";
        $accessToken = $option['t'] ?? bin2hex(random_bytes(8));
        $this->accessToken = $accessToken;
        $this->databaseName = "aoiawd-" .  explode(':', $this->config['logServer'])[2] ?? 'default';
        if (isset($option['h'])) {
            echo "AoiAWD: Data Visualization Tool & Main Server\r\n";
            echo "Usage: {$_SERVER['argv'][0]} [OPTIONS]\r\n";
            echo "\t -w [URI] HTTP server bind URI. Default: tcp://0.0.0.0:1337\r\n";
            echo "\t -l [URI] Log recoard server bind URI. Default: tcp://0.0.0.0:8023\r\n";
            echo "\t -m [URI] MongoDB server URI. Default: mongodb://127.0.0.1:27017\r\n";
            echo "\t -t [STRING] Access token. Default: [RANDOM]\r\n";
            echo "\t -h This help info\r\n";
            exit;
        }
    }

    public function run()
    {
        try {
            yield $this->apiServer->start();
            $this->logServer->start();
            Loop::repeat(1500, function () {
                WebsocketHandler::triggerNotify();
            });
        } catch (\Throwable $t) {
            $this->logger->alert($t);
        }
    }

    public function getLogReceiver()
    {
        return $this->logServer;
    }

    public function getUpTime()
    {
        return time() - $this->startTime;
    }

    public function increaseAlert()
    {
        $this->alertCount++;
        $this->triggerUpdate('alert');
    }

    public function getAlertCount()
    {
        return $this->alertCount;
    }

    public function triggerUpdate($type)
    {
        $this->lastUpdate = time();
        WebsocketHandler::notifyAll($type);
    }

    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    public function getDB(): Database
    {
        return $this->mongoDB;
    }

    static public function getInstance(): AoiAWD
    {
        return self::$_self_instance;
    }
    public function getConfig()
    {
        return $this->config;
    }
}
