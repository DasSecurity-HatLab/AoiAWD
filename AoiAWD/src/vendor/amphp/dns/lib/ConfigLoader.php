<?php

namespace Amp\Dns;

use Amp\Promise;

interface ConfigLoader
{
    public function loadConfig(): Promise;
}
