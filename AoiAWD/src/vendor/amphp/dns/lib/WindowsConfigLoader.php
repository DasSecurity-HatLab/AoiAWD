<?php

namespace Amp\Dns;

use Amp\Promise;
use Amp\WindowsRegistry\KeyNotFoundException;
use Amp\WindowsRegistry\WindowsRegistry;
use function Amp\call;

final class WindowsConfigLoader implements ConfigLoader
{
    private $hostLoader;

    public function __construct(HostLoader $hostLoader = null)
    {
        $this->hostLoader = $hostLoader ?? new HostLoader;
    }

    public function loadConfig(): Promise
    {
        return call(function () {
            $keys = [
                "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Services\\Tcpip\\Parameters\\NameServer",
                "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Services\\Tcpip\\Parameters\\DhcpNameServer",
            ];

            $reader = new WindowsRegistry;
            $nameserver = "";

            while ($nameserver === "" && ($key = \array_shift($keys))) {
                try {
                    $nameserver = yield $reader->read($key);
                } catch (KeyNotFoundException $e) {
                    // retry other possible locations
                }
            }

            if ($nameserver === "") {
                $interfaces = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Services\\Tcpip\\Parameters\\Interfaces";
                $subKeys = yield $reader->listKeys($interfaces);

                foreach ($subKeys as $key) {
                    foreach (["NameServer", "DhcpNameServer"] as $property) {
                        try {
                            $nameserver = yield $reader->read("{$key}\\{$property}");

                            if ($nameserver !== "") {
                                break 2;
                            }
                        } catch (KeyNotFoundException $e) {
                            // retry other possible locations
                        }
                    }
                }
            }

            if ($nameserver === "") {
                throw new ConfigException("Could not find a nameserver in the Windows Registry");
            }

            $nameservers = [];

            // Microsoft documents space as delimiter, AppVeyor uses comma, we just accept both
            foreach (\explode(" ", \strtr($nameserver, ",", " ")) as $nameserver) {
                $nameserver = \trim($nameserver);
                $ip = @\inet_pton($nameserver);

                if ($ip === false) {
                    continue;
                }

                if (isset($ip[15])) { // IPv6
                    $nameservers[] = "[" . $nameserver . "]:53";
                } else { // IPv4
                    $nameservers[] = $nameserver . ":53";
                }
            }

            $hosts = yield $this->hostLoader->loadHosts();

            return new Config($nameservers, $hosts);
        });
    }
}
