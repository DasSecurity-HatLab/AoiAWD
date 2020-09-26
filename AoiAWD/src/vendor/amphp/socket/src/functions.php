<?php

namespace Amp\Socket;

use Amp\CancellationToken;
use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use function Amp\call;

const LOOP_CONNECTOR_IDENTIFIER = Connector::class;

/**
 * Listen for client connections on the specified server address.
 *
 * If you want to accept TLS connections, you have to use `yield $socket->enableCrypto()` after accepting new clients.
 *
 * @param string $uri URI in scheme://host:port format. TCP is assumed if no scheme is present.
 * @param ServerListenContext $socketContext Context options for listening.
 * @param ServerTlsContext $tlsContext Context options for TLS connections.
 *
 * @return Server
 *
 * @throws SocketException If binding to the specified URI failed.
 * @throws \Error If an invalid scheme is given.
 */
function listen(string $uri, ServerListenContext $socketContext = null, ServerTlsContext $tlsContext = null): Server
{
    $socketContext = $socketContext ?? new ServerListenContext;

    $scheme = \strstr($uri, '://', true);

    if ($scheme === false) {
        $uri = 'tcp://' . $uri;
    } elseif (!\in_array($scheme, ['tcp', 'unix'])) {
        throw new \Error('Only tcp and unix schemes allowed for server creation');
    }

    if ($tlsContext) {
        $context = \array_merge(
            $socketContext->toStreamContextArray(),
            $tlsContext->toStreamContextArray()
        );
    } else {
        $context = $socketContext->toStreamContextArray();
    }

    $context = \stream_context_create($context);

    // Error reporting suppressed since stream_socket_server() emits an E_WARNING on failure (checked below).
    $server = @\stream_socket_server($uri, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);

    if (!$server || $errno) {
        throw new SocketException(\sprintf('Could not create server %s: [Error: #%d] %s', $uri, $errno, $errstr), $errno);
    }

    return new Server($server, ServerSocket::DEFAULT_CHUNK_SIZE);
}

/**
 * Create a new Datagram (UDP server) on the specified server address.
 *
 * @param string $uri URI in scheme://host:port format. UDP is assumed if no scheme is present.
 * @param ServerListenContext $socketContext Context options for listening.
 *
 * @return DatagramSocket
 *
 * @throws SocketException If binding to the specified URI failed.
 * @throws \Error If an invalid scheme is given.
 */
function endpoint(string $uri, ServerListenContext $socketContext = null): DatagramSocket
{
    $socketContext = $socketContext ?? new ServerListenContext;

    $scheme = \strstr($uri, '://', true);

    if ($scheme === false) {
        $uri = 'udp://' . $uri;
    } elseif ($scheme !== 'udp') {
        throw new \Error('Only udp scheme allowed for datagram creation');
    }

    $context = \stream_context_create($socketContext->toStreamContextArray());

    // Error reporting suppressed since stream_socket_server() emits an E_WARNING on failure (checked below).
    $server = @\stream_socket_server($uri, $errno, $errstr, STREAM_SERVER_BIND, $context);

    if (!$server || $errno) {
        throw new SocketException(\sprintf('Could not create datagram %s: [Error: #%d] %s', $uri, $errno, $errstr), $errno);
    }

    return new DatagramSocket($server, DatagramSocket::DEFAULT_CHUNK_SIZE);
}

/**
 * Set or access the global socket Connector instance.
 *
 * @param Connector|null $connector
 *
 * @return Connector
 */
function connector(Connector $connector = null): Connector
{
    if ($connector === null) {
        $connector = Loop::getState(LOOP_CONNECTOR_IDENTIFIER);
        if ($connector) {
            return $connector;
        }

        $connector = new DnsConnector;
    }

    Loop::setState(LOOP_CONNECTOR_IDENTIFIER, $connector);
    return $connector;
}

/**
 * Asynchronously establish a socket connection to the specified URI.
 *
 * @param string                 $uri URI in scheme://host:port format. TCP is assumed if no scheme is present.
 * @param ClientConnectContext   $socketContext Socket connect context to use when connecting.
 * @param CancellationToken|null $token
 *
 * @return Promise<\Amp\Socket\ClientSocket>
 */
function connect(string $uri, ClientConnectContext $socketContext = null, CancellationToken $token = null): Promise
{
    return connector()->connect($uri, $socketContext, $token);
}

/**
 * Asynchronously establish an encrypted TCP connection (non-blocking).
 *
 * Note: Once resolved the socket stream will already be set to non-blocking mode.
 *
 * @param string               $uri
 * @param ClientConnectContext $socketContext
 * @param ClientTlsContext     $tlsContext
 * @param CancellationToken    $token
 *
 * @return Promise<ClientSocket>
 */
function cryptoConnect(
    string $uri,
    ClientConnectContext $socketContext = null,
    ClientTlsContext $tlsContext = null,
    CancellationToken $token = null
): Promise {
    return call(function () use ($uri, $socketContext, $tlsContext, $token) {
        $tlsContext = $tlsContext ?? new ClientTlsContext;

        if ($tlsContext->getPeerName() === null) {
            $tlsContext = $tlsContext->withPeerName(\parse_url($uri, PHP_URL_HOST));
        }

        /** @var ClientSocket $socket */
        $socket = yield connect($uri, $socketContext, $token);

        $promise = $socket->enableCrypto($tlsContext);

        if ($token) {
            $deferred = new Deferred;
            $id = $token->subscribe([$deferred, 'fail']);

            $promise->onResolve(function ($exception) use ($id, $token, $deferred) {
                if ($token->isRequested()) {
                    return;
                }

                $token->unsubscribe($id);

                if ($exception) {
                    $deferred->fail($exception);
                    return;
                }

                $deferred->resolve();
            });

            $promise = $deferred->promise();
        }

        try {
            yield $promise;
        } catch (\Throwable $exception) {
            $socket->close();
            throw $exception;
        }

        return $socket;
    });
}

/**
 * Returns a pair of connected stream socket resources.
 *
 * @return resource[] Pair of socket resources.
 *
 * @throws \Amp\Socket\SocketException If creating the sockets fails.
 */
function pair(): array
{
    if (($sockets = @\stream_socket_pair(\stripos(PHP_OS, 'win') === 0 ? STREAM_PF_INET : STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP)) === false) {
        $message = 'Failed to create socket pair.';
        if ($error = \error_get_last()) {
            $message .= \sprintf(' Errno: %d; %s', $error['type'], $error['message']);
        }
        throw new SocketException($message);
    }

    return $sockets;
}
