<?php

namespace Amp\Socket;

interface ResourceSocket extends Socket
{
    /**
     * Raw stream socket resource.
     *
     * @return resource|null
     */
    public function getResource();
}
