<?php
system('make clean && make');
$pharPath = "guardian.phar";
unlink($pharPath);
$phar = new Phar($pharPath);
$stub = "#!/usr/bin/php\n<?php require_once('phar://'. __FILE__ .'/main.php');  __HALT_COMPILER();";
$phar->setStub($stub);
$phar->setSignatureAlgorithm(Phar::SHA1);
$phar->startBuffering();
$phar->addFile("./guardian", "guardian");
$phar["guardian"]->compress(Phar::GZ);
$phar->addFile("./src/main.php", "main.php");
$phar["main.php"]->compress(Phar::GZ);
$phar->stopBuffering();
`chmod +x $pharPath`;
`rm guardian`;
