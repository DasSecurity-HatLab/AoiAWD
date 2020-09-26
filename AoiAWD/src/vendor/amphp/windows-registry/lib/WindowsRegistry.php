<?php

namespace Amp\WindowsRegistry;

use Amp\ByteStream;
use Amp\Process\Process;
use Amp\Promise;
use function Amp\call;

class WindowsRegistry
{
    public function read(string $key): Promise
    {
        return call(function () use ($key) {
            $key = \strtr($key, '/', "\\");
            $parts = \explode("\\", $key);

            $value = \array_pop($parts);
            $key = \implode("\\", $parts);

            $lines = yield $this->query($key);

            $lines = \array_filter($lines, function ($line) {
                return '' !== $line && $line[0] === ' ';
            });

            $values = \array_map(function ($line) {
                return \preg_split("(\\s+)", \ltrim($line), 3);
            }, $lines);

            foreach ($values as $v) {
                if ($v[0] === $value) {
                    return $v[2];
                }
            }

            throw new KeyNotFoundException("Windows registry key '{$key}\\{$value}' not found.");
        });
    }

    public function listKeys(string $key): Promise
    {
        return call(function () use ($key) {
            $lines = yield $this->query($key);

            $lines = \array_filter($lines, function ($line) {
                return '' !== $line && $line[0] !== ' ';
            });

            return $lines;
        });
    }

    private function query(string $key): Promise
    {
        return call(function () use ($key) {
            if (0 !== \stripos(\PHP_OS, 'WIN')) {
                throw new \Error('Not running on Windows.');
            }

            $key = \strtr($key, '/', "\\");

            $cmd = \sprintf('reg query %s', \escapeshellarg($key));
            $process = new Process($cmd);
            yield $process->start();

            $stdout = yield ByteStream\buffer($process->getStdout());
            $code = yield $process->join();

            if ($code !== 0) {
                throw new KeyNotFoundException("Windows registry key '{$key}' not found.");
            }

            return \explode("\n", \str_replace("\r", '', $stdout));
        });
    }
}
