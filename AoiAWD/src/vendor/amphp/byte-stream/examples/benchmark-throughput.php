<?php

// Adopted from ReactPHP's stream package
// https://github.com/reactphp/stream/blob/b996af99fd1169ff74e93ef69c1513b7d0db19d0/examples/benchmark-throughput.php

use Amp\ByteStream\ResourceInputStream;
use Amp\ByteStream\ResourceOutputStream;
use Amp\Loop;

require __DIR__ . '/../vendor/autoload.php';

Loop::set(new Loop\NativeDriver());

$args = \getopt('i:o:t:');
$if = isset($args['i']) ? $args['i'] : '/dev/zero';
$of = isset($args['o']) ? $args['o'] : '/dev/null';
$t  = isset($args['t']) ? $args['t'] : 30;

// passing file descriptors requires mapping paths (https://bugs.php.net/bug.php?id=53465)
$if = \preg_replace('(^/dev/fd/)', 'php://fd/', $if);
$of = \preg_replace('(^/dev/fd/)', 'php://fd/', $of);

$stderr = new ResourceOutputStream(STDERR);
$in = new ResourceInputStream(\fopen($if, 'r'), 65536 /* Default size used by React to allow comparisons */);
$out = new ResourceOutputStream(\fopen($of, 'w'));

if (\extension_loaded('xdebug')) {
    $stderr->write('NOTICE: The "xdebug" extension is loaded, this has a major impact on performance.' . PHP_EOL);
}

try {
    if (!@\assert(false)) {
        $stderr->write("NOTICE: Assertions are enabled, this has a major impact on performance." . PHP_EOL);
    }
} catch (AssertionError $exception) {
    $stderr->write("NOTICE: Assertions are enabled, this has a major impact on performance." . PHP_EOL);
}

$stderr->write('piping from ' . $if . ' to ' . $of . ' (for max ' . $t . ' second(s)) ...'. PHP_EOL);

Loop::delay($t * 1000, [$in, "close"]);

Loop::run(function () use ($stderr, $in, $out) {
    $start = \microtime(true);

    while (($chunk = yield $in->read()) !== null) {
        yield $out->write($chunk);
    }

    $t = \microtime(true) - $start;

    $bytes = \ftell($out->getResource());

    $stderr->write('read ' . $bytes . ' byte(s) in ' . \round($t, 3) . ' second(s) => ' . \round($bytes / 1024 / 1024 / $t, 1) . ' MiB/s' . PHP_EOL);
    $stderr->write('peak memory usage of ' . \round(\memory_get_peak_usage(true) / 1024 / 1024, 1) . ' MiB' . PHP_EOL);
});
