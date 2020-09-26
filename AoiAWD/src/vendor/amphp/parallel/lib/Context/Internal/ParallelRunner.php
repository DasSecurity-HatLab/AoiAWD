<?php

namespace Amp\Parallel\Context\Internal;

use Amp\Loop;
use Amp\Parallel\Sync\Channel;
use Amp\Parallel\Sync\ExitFailure;
use Amp\Parallel\Sync\ExitSuccess;
use Amp\Parallel\Sync\SerializationException;
use Amp\Promise;
use function Amp\call;

/**
 * @codeCoverageIgnore Only executed in thread.
 */
final class ParallelRunner
{
    const EXIT_CHECK_FREQUENCY = 250;

    public static function run(Channel $channel, string $path, array $argv): void
    {
        Loop::unreference(Loop::repeat(self::EXIT_CHECK_FREQUENCY, function () {
            // Timer to give the chance for the PHP VM to be interrupted by Runtime::kill(), since system calls such as
            // select() will not be interrupted.
        }));

        try {
            if (!\is_file($path)) {
                throw new \Error(\sprintf("No script found at '%s' (be sure to provide the full path to the script)", $path));
            }

            $argc = \array_unshift($argv, $path);

            try {
                // Protect current scope by requiring script within another function.
                $callable = (function () use ($argc, $argv): callable { // Using $argc so it is available to the required script.
                    return require $argv[0];
                })();
            } catch (\TypeError $exception) {
                throw new \Error(\sprintf("Script '%s' did not return a callable function", $path), 0, $exception);
            } catch (\ParseError $exception) {
                throw new \Error(\sprintf("Script '%s' contains a parse error", $path), 0, $exception);
            }

            $result = new ExitSuccess(Promise\wait(call($callable, $channel)));
        } catch (\Throwable $exception) {
            $result = new ExitFailure($exception);
        }

        Promise\wait(call(function () use ($channel, $result) {
            try {
                yield $channel->send($result);
            } catch (SerializationException $exception) {
                // Serializing the result failed. Send the reason why.
                yield $channel->send(new ExitFailure($exception));
            }
        }));
    }
}
