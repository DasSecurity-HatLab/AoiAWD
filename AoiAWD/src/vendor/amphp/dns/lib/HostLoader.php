<?php

namespace Amp\Dns;

use Amp\Failure;
use Amp\Promise;
use Amp\Success;
use function Amp\call;

class HostLoader
{
    private $path;

    public function __construct(string $path = null)
    {
        $this->path = $path ?? $this->getDefaultPath();
    }

    private function getDefaultPath(): string
    {
        return \stripos(PHP_OS, "win") === 0
            ? 'C:\Windows\system32\drivers\etc\hosts'
            : '/etc/hosts';
    }

    protected function readFile(string $path): Promise
    {
        \set_error_handler(function (int $errno, string $message) use ($path) {
            throw new ConfigException("Could not read configuration file '{$path}' ({$errno}) $message");
        });

        try {
            // Blocking file access, but this file should be local and usually loaded only once.
            $fileContent = \file_get_contents($path);
        } catch (ConfigException $exception) {
            return new Failure($exception);
        } finally {
            \restore_error_handler();
        }

        return new Success($fileContent);
    }

    public function loadHosts(): Promise
    {
        return call(function () {
            try {
                $contents = yield $this->readFile($this->path);
            } catch (ConfigException $exception) {
                return [];
            }

            $data = [];

            $lines = \array_filter(\array_map("trim", \explode("\n", $contents)));

            foreach ($lines as $line) {
                if ($line[0] === "#") { // Skip comments
                    continue;
                }

                $parts = \preg_split('/\s+/', $line);

                if (!($ip = @\inet_pton($parts[0]))) {
                    continue;
                } elseif (isset($ip[4])) {
                    $key = Record::AAAA;
                } else {
                    $key = Record::A;
                }

                for ($i = 1, $l = \count($parts); $i < $l; $i++) {
                    try {
                        $normalizedName = normalizeName($parts[$i]);
                        $data[$key][$normalizedName] = $parts[0];
                    } catch (InvalidNameException $e) {
                        // ignore invalid entries
                    }
                }
            }

            // Windows does not include localhost in its host file. Fetch it from the system instead
            if (!isset($data[Record::A]["localhost"]) && !isset($data[Record::AAAA]["localhost"])) {
                // PHP currently provides no way to **resolve** IPv6 hostnames (not even with fallback)
                $local = \gethostbyname("localhost");
                if ($local !== "localhost") {
                    $data[Record::A]["localhost"] = $local;
                } else {
                    $data[Record::AAAA]["localhost"] = "::1";
                }
            }

            return $data;
        });
    }
}
