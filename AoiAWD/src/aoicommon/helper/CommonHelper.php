<?php

namespace aoicommon\helper;

use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Monolog\Logger;
use function Amp\ByteStream\getStdout;
use Psr\Log\LoggerInterface as PsrLogger;
use Amp\Loop;
use Amp\ByteStream\ResourceOutputStream;
use aoiawd\AoiAWD;

abstract class CommonHelper
{
    static private $logFile;
    static private $logger;

    static private function getLogFileHandler(): StreamHandler
    {
        if (self::$logFile == null) {
            self::$logFile = "server-" . (explode(':', AoiAWD::getInstance()->getConfig()['logServer'])[2] ?? 'default') . ".log";
        }
        static $key = self::class . '\\logfile';
        $stream = Loop::getState($key);
        if (!$stream) {
            $logFile = fopen(self::$logFile, "c+");
            fseek($logFile, 0, SEEK_END);
            $stream = new ResourceOutputStream($logFile);
            Loop::setState($key, $stream);
        }
        return new StreamHandler($stream);
    }

    static public function getLogger($loggerName): PsrLogger
    {
        $logHandler = new StreamHandler(getStdout());
        $logHandler->setFormatter(new ConsoleFormatter());
        $logger = new Logger($loggerName);
        $logger->pushHandler($logHandler);
        $logger->pushHandler(self::getLogFileHandler());
        return $logger;
    }

    static public function enableGCTimer()
    {
        Loop::repeat(32000000, function () {
            self::selfLogger()->debug("GC Launched...");
            gc_collect_cycles();
            gc_mem_caches();
        });
    }

    static private function selfLogger()
    {
        if (self::$logger == null) {
            self::$logger = self::getLogger(self::class);
        }
        return self::$logger;
    }
}
