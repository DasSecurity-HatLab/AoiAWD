<?php
$pharPath = "tapeworm.phar";
unlink($pharPath);
$phar = new Phar($pharPath);
$stub = "#!/usr/bin/php\n<?php require_once('phar://'. __FILE__ .'/main.php');  __HALT_COMPILER();";
$phar->setStub($stub);
$phar->setSignatureAlgorithm(Phar::SHA1);
$phar->startBuffering();
$scanner = new DirScanner("./src");
$files = $scanner->getFiles();
foreach ($files as $file) {
    $target = ltrim($file, './src/');
    if (is_readable($file) && is_file($file)) {
        echo "Insert: $file to $target" . PHP_EOL;
        $phar->addFile($file, $target);
        $phar[$target]->compress(Phar::GZ);
    }
}
$phar->stopBuffering();
`chmod +x $pharPath`;

class DirScanner
{
    private $files;

    public function __construct($dir)
    {
        $this->files = array();
        $this->scan($dir);
    }

    private function scan($dir)
    {
        $file = scandir($dir);
        foreach ($file as $f) {
            if ($f !== '.' && $f != '..') {
                $name = $dir . DIRECTORY_SEPARATOR  . $f;
                $this->files[] = $name;
                if (is_dir($name)) {
                    $this->scan($name);
                }
            }
        }
    }

    public function getFiles()
    {
        return $this->files;
    }
}
