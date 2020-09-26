<?php

use Amp\Http\Rfc7230;

require __DIR__ . "/../../vendor/autoload.php";

$rawHeaders = "Server: Microsoft-IIS/5.0
Date: Tue, 31 Oct 2006 08:00:29 GMT
Connection: close
Allow: GET, HEAD, POST, TRACE, OPTIONS
Content-Length: 0
X-No-Value:
X-No-Whitespace: Test
X-Trailing-Whitespace:  	Foobar		  
";

// Normalize line endings, which might be broken by Git otherwise
$rawHeaders = \str_replace("\n", "\r\n", \str_replace("\r\n", "\n", $rawHeaders));
$headers = Rfc7230::parseHeaders($rawHeaders);

$start = \microtime(true);

for ($i = 0; $i < 300000; $i++) {
    Rfc7230::formatHeaders($headers);
}

print(\microtime(true) - $start) . PHP_EOL;
