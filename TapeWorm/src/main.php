<?php
$argv = getopt("d:s:f:h");
$server_uri = isset($argv['s']) ? $argv['s'] : '127.0.0.1:8023';
$dir = isset($argv['d']) ? $argv['d'] : false;
$monitor_path = isset($argv['f']) ? realpath($argv['f']) : false;

if (!$dir || isset($argv['h'])) {
    echo "TapeWorm: AoiAWD PHP WebMonitor Tool\r\n";
    echo "Usage: {$_SERVER['argv'][0]} [PATH]\r\n";
    echo "\t -d [PATH] WebMonitor inject dir.\r\n";
    echo "\t -s [URI] Log recoard server URI. Default: 127.0.0.1:8023\r\n";
    echo "\t -f [PATH] Inject file path. Default: {\$dir}\r\n";
    echo "\t -h This help info\r\n";
    exit;
}
$dir = realpath($dir);
if (!$dir) {
    echo "Please check file path\n";
    exit;
}
$socket = @stream_socket_client("tcp://{$server_uri}");
if (!$socket) {
    echo "Can not connect to log center.\n";
    exit;
}
fwrite($socket, json_encode(['type' => 'ping']) . "\n");
$data = json_decode(fgets($socket));
if ($data->type !== 'pong') {
    echo "Failed to ping log center.\n";
    exit;
}
$monitor = file_get_contents(__DIR__ . '/WebMonitor.php');
$monitor = str_replace('SERVER_URI', $server_uri, $monitor);
if (file_exists('.tapeworm.installed')) {
    $monitor_path = file_get_contents('.tapeworm.installed');
} else {
    if (!$monitor_path) {
        $monitor_path = "{$dir}/TapeWorm." . uniqid() . ".php";
    } else {
        $monitor_path .= "/TapeWorm." . uniqid() . ".php";
    }
    file_put_contents('.tapeworm.installed', $monitor_path);
}
$ds = new DirScanner($dir);
echo "Installing...\n";
foreach ($ds->getFiles() as $f) {
    $d = explode('.', $f);
    $ext = end($d);
    if ($ext == 'php') {
        echo $f . PHP_EOL;
        $buffer = file_get_contents($f);
        $mark = "/*TAPEWORMINSTALLED*/";
        if (!stristr($buffer, $mark)) {
            $namespace_rexp = '/^\s*(namespace\s+.*;)/mi';
            if (preg_match($namespace_rexp, $buffer, $match)) {
                $buffer = preg_replace($namespace_rexp, $match[1] . "\n" . " {$mark} include '$monitor_path';\n{$mark}\n", $buffer, 1);
            } else {
                $buffer = "<?php {$mark} include '$monitor_path'; ?>\n{$buffer}";
            }
            file_put_contents($f, $buffer);
        }
    }
}
file_put_contents($monitor_path, $monitor);
system("chmod 0644 '{$monitor_path}'");
echo "Done!\n";

class DirScanner
{

    private $files;

    public function __construct($dir)
    {
        $this->files = array();
        $this->scan($dir);
    }

    public function scan($dir)
    {
        $file = glob("$dir/*");
        foreach ($file as $f) {
            if ($f !== '.' && $f != '..') {
                $name = $f;
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
