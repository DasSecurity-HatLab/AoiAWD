---
title: Processes
permalink: /processes
---
`Process` simplifies writing and running PHP in parallel. A script written to be run in parallel must return a callable that will be run in a child process. The callable receives a single argument – an instance of `Channel` that can be used to send data between the parent and child processes. Any serializable data can be sent across this channel. The `Process` object is the other end of the communication channel, as it implements `Context`, which extends `Channel`.

In the example below, a child process is used to call a blocking function (`file_get_contents()` is only an example of a blocking function, use [Artax](https://amphp.org/artax) for non-blocking HTTP requests). The result of that function is then sent back to the parent using the `Channel` object. The return value of the child process callable is available using the `Process::join()` method.

## Child Process

```php
# child.php

use Amp\Parallel\Sync\Channel;

return function (Channel $channel): \Generator {
    $url = yield $channel->receive();

    $data = file_get_contents($url); // Example blocking function

    yield $channel->send($data);

    return 'Any serializable data';
});
```

## Parent Process

```php
# parent.php

use Amp\Loop;
use Amp\Parallel\Context\Process;

Loop::run(function () {
    $process = new Process(__DIR__ . '/child.php');

    $pid = yield $process->start();

    $url = 'https://google.com';

    yield $process->send($url);

    $requestData = $process->receive();

    printf("Received %d bytes from %s\n", \strlen($requestData), $url);

    $returnValue = $process->join();

    printf("Child processes exited with '%s'\n", $returnValue);
});
```

Child processes are also great for CPU-intensive operations such as image manipulation or for running daemons that perform periodic tasks based on input from the parent.
