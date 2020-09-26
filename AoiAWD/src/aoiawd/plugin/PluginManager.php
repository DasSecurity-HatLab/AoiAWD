<?php
namespace aoiawd\plugin;

use Psr\Log\LoggerInterface as PsrLogger;

class PluginManager
{
    static private $__self_instance;

    private $server;

    private $logger;

    private $baseDir;

    private $pluginReg = [];

    private $pluginHook = [];

    private $invoker;

    public function __construct($server, PsrLogger $logger)
    {
        self::$__self_instance = $this;
        $this->server = $server;
        $this->logger = $logger;
        $this->baseDir = realpath('.') . "/plugins";
        if (!file_exists($this->baseDir)) {
            mkdir($this->baseDir);
        }
        $this->loadPlugin();
    }

    public function loadPlugin()
    {
        $path = \scandir($this->baseDir);
        foreach ($path as $file) {
            $extension = explode('.', $file);
            $extension = end($extension);
            if ($extension == "php") {
                if (!in_array($file, $this->pluginReg)) {
                    try {
                        $this->logger->info("Loading plugin: {$file}");
                        include $this->baseDir . "/$file";
                        $this->pluginReg[] = $file;
                        $this->logger->info("Plugin Loaded.");
                    } catch (\Throwable $t) {
                        $this->logger->alert("Can not load plugin on: {$file}\nReason: {$t->getMessage()}");
                    }
                }
            }
        }
    }

    public function invoke($caller, string $routine, string $operation, $data = null)
    {
        $routine = strtolower($routine);
        $operation = strtolower($operation);
        $result = $data;
        if (isset($this->pluginHook[$routine][$operation])) {
            $this->invoker = $caller;
            $callbacks = $this->pluginHook[$routine][$operation];
            foreach ($callbacks as $hook) {
                try {
                    $temp = $hook($result);
                    $result = $temp;
                } catch (\Throwable $t) {
                    $this->logger->alert("Excepton on Routine: {$routine} Operation: {$operation}\nReason: {$t->getMessage()}");
                }
            }
        }
        $this->invoker = null;
        return $result;
    }

    public function register(string $routine, string $operation, callable $callback)
    {
        $routine = strtolower($routine);
        $operation = strtolower($operation);
        $this->pluginHook[$routine][$operation][] = $callback;
        $this->logger->info("Register Routine: {$routine} Operation: {$operation}");
    }

    public function listPlugin()
    {
        return $this->pluginReg;
    }

    public function getServer()
    {
        return $this->server;
    }

    public function getInvoker()
    {
        return $this->invoker;
    }

    static public function getInstance()
    {
        return self::$__self_instance;
    }
}
