<?php

namespace Amp\Socket;

/**
 * @see ServerTlsContext::withDefaultCertificate()
 * @see ServerTlsContext::withCertificates()
 */
class Certificate
{
    private $certFile;
    private $keyFile;

    /**
     * @param string      $certFile Certificate file with the certificate + intermediaries.
     * @param string|null $keyFile Key file with the corresponding private key or `null` if the key is in $certFile.
     */
    public function __construct(string $certFile, string $keyFile = null)
    {
        $this->certFile = $certFile;
        $this->keyFile = $keyFile ?? $certFile;
    }

    /**
     * @return string
     */
    public function getCertFile(): string
    {
        return $this->certFile;
    }

    /**
     * @return string
     */
    public function getKeyFile(): string
    {
        return $this->keyFile;
    }
}
