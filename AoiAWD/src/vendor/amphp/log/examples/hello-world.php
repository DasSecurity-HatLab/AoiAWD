<?php

use Amp\ByteStream\ResourceOutputStream;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Monolog\Logger;

require dirname(__DIR__) . '/vendor/autoload.php';

$handler = new StreamHandler(new ResourceOutputStream(\STDOUT));
$handler->setFormatter(new ConsoleFormatter);

$logger = new Logger('hello-world');
$logger->pushHandler($handler);

$logger->debug("Hello, world!");
$logger->info("Hello, world!");
$logger->notice("Hello, world!");
$logger->error("Hello, world!");
$logger->alert("Hello, world!");
