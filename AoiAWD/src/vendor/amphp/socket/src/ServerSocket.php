<?php

namespace Amp\Socket;

use Amp\ByteStream\ClosedException;
use Amp\Failure;
use Amp\Promise;

class ServerSocket extends ResourceStreamSocket
{
    /** @inheritdoc */
    final public function enableCrypto(): Promise
    {
        if (($resource = $this->getResource()) === null) {
            return new Failure(new ClosedException("The socket has been closed"));
        }

        $ctx = \stream_context_get_options($resource);
        if (empty($ctx['ssl'])) {
            return new Failure(new CryptoException(
                "Can't enable TLS without configuration. " .
                "If you used Amp\\Socket\\listen(), be sure to pass a ServerTlsContext as third argument, " .
                "otherwise set the 'ssl' context option to the PHP stream resource."
            ));
        }

        return Internal\enableCrypto($resource, [], true);
    }
}
