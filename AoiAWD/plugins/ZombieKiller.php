<?php
namespace aoiawd\plugin;

new ZombieKiller(PluginManager::getInstance());

class ZombieKiller
{
    /** @var PluginManager */
    private $pluginManager;
    private $pathCache = [];
    public function __construct($manager)
    {
        $this->pluginManager = $manager;
        $this->pluginManager->register('FileSystem', 'processLog', [$this, 'processPath']);
    }

    public function processPath($data)
    {
        $path = &$data['path'];
        if (isset($this->pathCache[$path])) {
            $stat = &$this->pathCache[$path];
            if (!$stat[2]) {
                if ($stat[1] >= 50) {
                    $this->pluginManager->getInvoker()->setAlert('ZombieKiller', "发现疑似不死马行为，位于路径: {$path}");
                    $stat[2] = true;
                } else {
                    if ((time() - $stat[0]) <= 2) {
                        $stat[1]++;
                    } else {
                        $stat[0] = time();
                        $stat[1] = 0;
                    }
                }
            } else {
                $this->pluginManager->getInvoker()->abortInsert();
                if ((time() - $stat[0]) >= 120) {
                    $stat[0] = time();
                    $stat[1] = 0;
                    $stat[2] = false;
                }
            }
        } else {
            $this->pathCache[$data['path']] = [time(), 0, false];
        }
        if (count($this->pathCache) > 1000) {
            $this->pathCache = [];
        }
    }
}
