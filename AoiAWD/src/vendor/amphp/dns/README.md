# dns

[![Build Status](https://img.shields.io/travis/amphp/dns/master.svg?style=flat-square)](https://travis-ci.org/amphp/dns)
[![CoverageStatus](https://img.shields.io/coveralls/amphp/dns/master.svg?style=flat-square)](https://coveralls.io/github/amphp/dns?branch=master)
![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)

`amphp/dns` provides asynchronous DNS name resolution for [Amp](https://github.com/amphp/amp).

## Installation

```bash
composer require amphp/dns
```

## Example

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Amp\Dns;
use Amp\Loop;

Loop::run(function () {
    $githubIpv4 = yield Dns\resolve("github.com", Dns\Record::A);
    var_dump($githubIpv4);

    $googleIpv4 = Amp\Dns\resolve("google.com", Dns\Record::A);
    $googleIpv6 = Amp\Dns\resolve("google.com", Dns\Record::AAAA);

    $firstGoogleResult = yield Amp\Promise\first([$googleIpv4, $googleIpv6]);
    var_dump($firstGoogleResult);
    
    $combinedGoogleResult = yield Amp\Dns\resolve("google.com");
    var_dump($combinedGoogleResult);
    
    $googleMx = yield Amp\Dns\query("google.com", Amp\Dns\Record::MX);
    var_dump($googleMx);
});
```
