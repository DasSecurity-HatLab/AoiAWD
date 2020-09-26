---
title: Asynchronous DNS Resolution
permalink: /
---
`amphp/dns` provides asynchronous DNS name resolution for [Amp](http://amphp.org/amp).

## Installation

```bash
composer require amphp/dns
```

## Usage

### Configuration

`amphp/dns` automatically detects the system configuration and uses it. On Unix-like systems it reads `/etc/resolv.conf` and respects settings for nameservers, timeouts, and attempts. On Windows it looks up the correct entries in the Windows Registry and takes the listed nameservers. You can pass a custom `ConfigLoader` instance to `BasicResolver` to load another configuration, such as a static config.

It respects the system's hosts file on Unix and Windows based systems, so it works just fine in environments like Docker with named containers.

The package uses a global default resolver with can be accessed and changed via `Amp\Dns\resolver()`. If an argument other than `null` is given, the given resolver is used as global instance. The instance is automatically bound to the current event loop. If you replace the event loop via `Amp\Loop::set()`, then you have to set a new global resolver.

Usually you don't have to change the resolver. If you want to use a custom configuration for a certain request, you just create a new resolver instance and use that instead of changing the global one.

### Address Resolution

`Amp\Dns\resolve` provides hostname to IP address resolution. It returns an array of IPv4 and IPv6 addresses by default. The type of IP addresses returned can be restricted by passing a second argument with the respective type.

```php
// Example without type restriction. Will return IPv4 and / or IPv6 addresses.
// What's returned depends on what's available for the given hostname.

/** @var Amp\Dns\Record[] $records */
$records = yield Amp\Dns\resolve("github.com");
```

```php
// Example with type restriction. Will throw an exception if there are no A records.

/** @var Amp\Dns\Record[] $records */
$records = yield Amp\Dns\resolve("github.com", Amp\Dns\Record::A);
```

### Custom Queries

`Amp\Dns\query` supports the various other DNS record types such as `MX`, `PTR`, or `TXT`. It automatically rewrites passed IP addresses for `PTR` lookups.
 
```php
/** @var Amp\Dns\Record[] $records */
$records = Amp\Dns\query("google.com", Amp\Dns\Record::MX);
```

```php
/** @var Amp\Dns\Record[] $records */
$records = Amp\Dns\query("8.8.8.8", Amp\Dns\Record::PTR);
```

### Caching

The `BasicResolver` caches responses by default in an `Amp\Cache\ArrayCache`. You can set any other `Amp\Cache\Cache` implementation by creating a custom instance of `BasicResolver` and setting that via `Amp\Dns\resolver()`, but it's usually unnecessary. If you have a lot of very short running scripts, you might want to consider using a local DNS resolver with a cache instead of setting a custom cache implementation, such as `dnsmasq`. 

### Reloading Configuration

The `BasicResolver` (which is the default resolver shipping with that package) will cache the configuration of `/etc/resolv.conf` / the Windows Registry and the read host files by default. If you wish to reload them, you can set a periodic timer that requests a background reload of the configuration.

```php
Loop::repeat(60000, function () use ($resolver) {
    yield Amp\Dns\resolver()->reloadConfig();
});
```

{:.note}
> The above code relies on the resolver not being changed. `reloadConfig` is specific to `BasicResolver` and is not part of the `Resolver` interface. You might want to guard the reloading with an `instanceof` check or manually set a `BasicResolver` instance on startup to be sure it's an instance of `BasicResolver`.
