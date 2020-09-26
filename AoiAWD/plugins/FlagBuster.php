<?php
namespace aoiawd\plugin;

new FlagBuster(PluginManager::getInstance());

class FlagBuster
{
    /** @var PluginManager */
    private $pluginManager;

    public function __construct($manager)
    {
        $this->pluginManager = $manager;
        $this->pluginManager->register('Web', 'processLog', [$this, 'processWebBuffer']);
    }

    public function processWebBuffer($data)
    {
        $buffer = &$data['buffer'];
        $flagCount = 0;
        $fakeFlag = $this->generateFakeFlag();
        $buffer = preg_replace('/\{\"flag\"\:\"(.*)\"/mi', $fakeFlag, $buffer, -1, $flagCount);
        if ($flagCount > 0) {
            $this->pluginManager->getInvoker()->setAlert('FlagBuster', "发现本次Web应答包含flag字段，已被替换为: {$fakeFlag}");
        }
        return $data;
    }

    private function generateFakeFlag()
    {
        // return "flag{" . uniqid() . "}";
        // return "flag{" . md5(uniqid()) . "}";
        // return "flag{" . sha1(uniqid()) . "}";
        return '{"flag":"' . $this->uuid() . '"';
    }

    private function uuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
