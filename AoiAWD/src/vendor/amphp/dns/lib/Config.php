<?php

namespace Amp\Dns;

final class Config
{
    private $nameservers;
    private $knownHosts;
    private $timeout;
    private $attempts;

    public function __construct(array $nameservers, array $knownHosts = [], int $timeout = 3000, int $attempts = 2)
    {
        if (\count($nameservers) < 1) {
            throw new ConfigException("At least one nameserver is required for a valid config");
        }

        foreach ($nameservers as $nameserver) {
            $this->validateNameserver($nameserver);
        }

        if ($timeout < 0) {
            throw new ConfigException("Invalid timeout ({$timeout}), must be 0 or greater");
        }

        if ($attempts < 1) {
            throw new ConfigException("Invalid attempt count ({$attempts}), must be 1 or greater");
        }

        $this->nameservers = $nameservers;
        $this->knownHosts = $knownHosts;
        $this->timeout = $timeout;
        $this->attempts = $attempts;
    }

    private function validateNameserver($nameserver)
    {
        if (!$nameserver || !\is_string($nameserver)) {
            throw new ConfigException("Invalid nameserver: {$nameserver}");
        }

        if ($nameserver[0] === "[") { // IPv6
            $addr = \strstr(\substr($nameserver, 1), "]", true);
            $port = \substr($nameserver, \strrpos($nameserver, "]") + 1);

            if ($port !== "" && !\preg_match("(^:(\\d+)$)", $port, $match)) {
                throw new ConfigException("Invalid nameserver: {$nameserver}");
            }

            $port = $port === "" ? 53 : \substr($port, 1);
        } else { // IPv4
            $arr = \explode(":", $nameserver, 2);

            if (\count($arr) === 2) {
                list($addr, $port) = $arr;
            } else {
                $addr = $arr[0];
                $port = 53;
            }
        }

        $addr = \trim($addr, "[]");
        $port = (int) $port;

        if (!$inAddr = @\inet_pton($addr)) {
            throw new ConfigException("Invalid server IP: {$addr}");
        }

        if ($port < 1 || $port > 65535) {
            throw new ConfigException("Invalid server port: {$port}");
        }
    }

    public function getNameservers(): array
    {
        return $this->nameservers;
    }

    public function getKnownHosts(): array
    {
        return $this->knownHosts;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }
}
