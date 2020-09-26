<?php

namespace Amp\Dns;

use Amp\Failure;
use Amp\Promise;
use Amp\Success;
use function Amp\call;

class UnixConfigLoader implements ConfigLoader
{
    private $path;
    private $hostLoader;

    public function __construct(string $path = "/etc/resolv.conf", HostLoader $hostLoader = null)
    {
        $this->path = $path;
        $this->hostLoader = $hostLoader ?? new HostLoader;
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

    final public function loadConfig(): Promise
    {
        return call(function () {
            $nameservers = [];
            $timeout = 3000;
            $attempts = 2;

            $fileContent = yield $this->readFile($this->path);

            $lines = \explode("\n", $fileContent);

            foreach ($lines as $line) {
                $line = \preg_split('#\s+#', $line, 2);

                if (\count($line) !== 2) {
                    continue;
                }

                list($type, $value) = $line;

                if ($type === "nameserver") {
                    $value = \trim($value);
                    $ip = @\inet_pton($value);

                    if ($ip === false) {
                        continue;
                    }

                    if (isset($ip[15])) { // IPv6
                        $nameservers[] = "[" . $value . "]:53";
                    } else { // IPv4
                        $nameservers[] = $value . ":53";
                    }
                } elseif ($type === "options") {
                    $optline = \preg_split('#\s+#', $value, 2);

                    if (\count($optline) !== 2) {
                        continue;
                    }

                    list($option, $value) = $optline;

                    switch ($option) {
                        case "timeout":
                            $timeout = (int) $value;
                            break;

                        case "attempts":
                            $attempts = (int) $value;
                    }
                }
            }

            $hosts = yield $this->hostLoader->loadHosts();

            return new Config($nameservers, $hosts, $timeout, $attempts);
        });
    }
}
