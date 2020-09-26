<?php

namespace aoiawd\plugin;

new KingWatcher(PluginManager::getInstance());

class KingWatcher
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
        if (stristr($path, 'score_points')) {
            $this->pluginManager->getInvoker()->setAlert('KingWatcher', "可能有人动了你的蛋糕");
        }
    }
}
