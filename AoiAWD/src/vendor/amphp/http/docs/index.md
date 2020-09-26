---
title: Introduction
permalink: /
---
This package provides basic primitives needed for HTTP clients and servers.

 - [Cookies](./cookies.md)
 - [Headers](./headers.md)
 
## Status Codes

HTTP status codes are made human readable via `Amp\Http\Status`.
It includes a constant for each IANA registered status code.
Additionally, a default reason is available via `Http::getReason($code)`. 
