Basic HTTP primitives which can be shared by servers and clients.

## Installation

This package can be installed as a [Composer](https://getcomposer.org/) dependency.

```bash
composer require amphp/http
```

## Documentation

Documentation can be found on [amphp.org/http](https://amphp.org/http) as well as in the [`./docs`](./docs) directory.

## Versioning

`amphp/http` follows the [semver](http://semver.org/) semantic versioning specification like all other `amphp` packages.

> **Note:** BC breaks that are strictly required for RFC compliance are not considered BC breaks.
> These include cases like wrong quote handling for cookies, where the RFC isn't too clear.
>
> A lax parser will however not be changed unless it is necessary for security reasons.

## Requirements

- PHP 7.0+

## Security

If you discover any security related issues, please email [`contact@amphp.org`](mailto:contact@amphp.org) instead of using the issue tracker.

## License

The MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information.
