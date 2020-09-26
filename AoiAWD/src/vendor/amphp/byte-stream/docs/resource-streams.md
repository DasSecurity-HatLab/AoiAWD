---
title: Resource Streams
permalink: /resource-streams
---
This package abstracts PHP's stream resources with `ResourceInputStream` and `ResourceOutputStream`. They automatically set the passed resource to non-blocking and allow reading and writing like any other `InputStream` / `OutputStream`. They also handle backpressure automatically by disabling the read watcher in case there's no read request and only activate a write watcher if the underlying write buffer is already full, which makes them very efficient.
