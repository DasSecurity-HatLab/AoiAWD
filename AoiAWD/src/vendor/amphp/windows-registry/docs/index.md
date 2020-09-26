---
title: Windows Registry
permalink: /
---
`amphp/windows-registry` is a small helper package to ease reading the Windows registry. This might be necessary to access system settings such as the default DNS server.

## Installation

```
composer require amphp/windows-registry
```

## Usage

`WindowsRegistry` has the two methods `listKeys` and `read`.

`listKeys` fetches all sub-keys of one key. `read` reads the value of the key. Note that `read` doesn't convert any values and returns them as they're printed by `reg query %s`.
