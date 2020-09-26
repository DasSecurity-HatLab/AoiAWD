<?php
include __DIR__ . "/ClassLoader.php";
$classloader = new ClassLoader();
$classloader->addPath(__DIR__);
$classloader->register();