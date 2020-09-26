<?php
$argv = getopt("i:o:s:h");
$input_path = isset($argv['i']) ? $argv['i'] : false;
$output_path = isset($argv['o']) ? $argv['o'] : "{$input_path}.guardianed";
$server_uri = isset($argv['s']) ? $argv['s'] : '127.0.0.1:8023';

if (!$input_path || isset($argv['h'])) {
    echo "Guardian: AoiAWD ELF PWNMonitor Tool\r\n";
    echo "Usage: {$_SERVER['argv'][0]} [PATH]\r\n";
    echo "\t -i [PATH] Original ELF.\r\n";
    echo "\t -o [PATH] Path of patched ELF. Default: {\$OriginalELF}.guardianed\r\n";
    echo "\t -s [URI] Log recoard server URI. Default: 127.0.0.1:8023\r\n";
    echo "\t -h This help info\r\n";
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

if (!file_exists($input_path)) {
    echo "Can't open {$input_path}\n";
    exit;
}

$guardian_path = __DIR__ . '/guardian';

$buffer = file_get_contents($guardian_path) . file_get_contents($input_path) . pack("L", filesize($guardian_path)) . pack("N", ip2long(explode(":", $server_uri)[0])) . pack("n", (int) explode(":", $server_uri)[1]);

file_put_contents($output_path, $buffer);

chmod($output_path, 0755);

echo "Patched ELF: {$output_path}\n";
exit;
