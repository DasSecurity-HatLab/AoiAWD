<?php

use Amp\Http\Rfc7230;

require __DIR__ . "/../vendor/autoload.php";

$rawHeaders = "Server: GitHub.com\r\n"
    . "Date: Tue, 31 Oct 2006 08:00:29 GMT\r\n"
    . "Connection: close\r\n"
    . "Content-Length: 0\r\n";

$headers = Rfc7230::parseHeaders($rawHeaders);

\var_dump($headers);
