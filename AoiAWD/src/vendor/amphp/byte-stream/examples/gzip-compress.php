<?php

use Amp\ByteStream\ResourceInputStream;
use Amp\ByteStream\ResourceOutputStream;
use Amp\ByteStream\ZlibOutputStream;
use Amp\Loop;

require __DIR__ . "/../vendor/autoload.php";

Loop::run(function () {
    $stdin = new ResourceInputStream(STDIN);
    $stdout = new ResourceOutputStream(STDOUT);

    $gzout = new ZlibOutputStream($stdout, ZLIB_ENCODING_GZIP);

    while (($chunk = yield $stdin->read()) !== null) {
        yield $gzout->write($chunk);
    }
});
