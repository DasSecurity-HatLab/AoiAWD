# file

[![Build Status](https://img.shields.io/travis/amphp/file/master.svg?style=flat-square)](https://travis-ci.org/amphp/file)
[![CoverageStatus](https://img.shields.io/coveralls/amphp/file/master.svg?style=flat-square)](https://coveralls.io/github/amphp/file?branch=master)
![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)

`amphp/file` allows non-blocking access to the filesystem for [Amp](https://github.com/amphp/amp).

## Installation

This package can be installed as a [Composer](https://getcomposer.org/) dependency.

```bash
composer require amphp/file
```

## Optional Extension Backends

Extensions allow to use threading in the background instead of using multiple processes.
 
 - [eio](https://pecl.php.net/package/eio)
 - [php-uv](https://github.com/bwoebi/php-uv)
 - [pthreads](https://github.com/krakjoe/pthreads)

`amphp/file` works out of the box without any PHP extensions. It uses multi-processing by default, but also comes with a blocking driver that just uses PHP's blocking functions in the current process.

## Versioning

`amphp/file` follows the [semver](http://semver.org/) semantic versioning specification like all other `amphp` packages.

## Security

If you discover any security related issues, please email [`me@kelunik.com`](mailto:me@kelunik.com) instead of using the issue tracker.

## License

The MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information.