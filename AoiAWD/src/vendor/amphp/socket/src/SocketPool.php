<?php

namespace Amp\Socket;

use Amp\CancellationToken;
use Amp\Promise;

/**
 * Allows pooling of connections for stateless protocols.
 */
interface SocketPool
{
    /**
     * Checkout a socket from the specified URI authority.
     *
     * The resulting socket resource should be checked back in via `SocketPool::checkin()` once the calling code is
     * finished with the stream (even if the socket has been closed). Failure to checkin sockets will result in memory
     * leaks and socket queue blockage. Instead of checking the socket in again, it can also be cleared.
     *
     * @param string            $uri A string of the form tcp://example.com:80 or tcp://192.168.1.1:443.
     * @param CancellationToken $token Optional cancellation token to cancel the checkout request.
     *
     * @return Promise<ClientSocket> Resolves to a ClientSocket instance once a connection is available.
     */
    public function checkout(string $uri, CancellationToken $token = null): Promise;

    /**
     * Return a previously checked-out socket to the pool so it can be reused.
     *
     * @param ClientSocket $socket Socket instance.
     *
     * @throws \Error If the provided resource is unknown to the pool.
     */
    public function checkin(ClientSocket $socket);

    /**
     * Remove the specified socket from the pool.
     *
     * @param ClientSocket $socket Socket instance.
     *
     * @throws \Error If the provided resource is unknown to the pool.
     */
    public function clear(ClientSocket $socket);
}
