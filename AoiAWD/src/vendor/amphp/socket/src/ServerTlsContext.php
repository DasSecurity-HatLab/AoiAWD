<?php

namespace Amp\Socket;

final class ServerTlsContext
{
    const TLSv1_0 = \STREAM_CRYPTO_METHOD_TLSv1_0_SERVER;
    const TLSv1_1 = \STREAM_CRYPTO_METHOD_TLSv1_1_SERVER;
    const TLSv1_2 = \STREAM_CRYPTO_METHOD_TLSv1_2_SERVER;

    /** @var int */
    private $minVersion = \STREAM_CRYPTO_METHOD_TLSv1_0_SERVER;
    /** @var null|string */
    private $peerName;
    /** @var bool */
    private $verifyPeer = false;
    /** @var int */
    private $verifyDepth = 10;
    /** @var null|string */
    private $ciphers;
    /** @var null|string */
    private $caFile;
    /** @var null|string */
    private $caPath;
    /** @var bool */
    private $capturePeer = false;
    /** @var null|Certificate */
    private $defaultCertificate;
    /** @var Certificate[] */
    private $certificates = [];
    /** @var int */
    private $securityLevel = 2;

    /**
     * Minimum TLS version to negotiate.
     *
     * Defaults to TLS 1.0.
     *
     * @param int $version `ServerTlsContext::TLSv1_0`, `ServerTlsContext::TLSv1_1`, or `ServerTlsContext::TLSv1_2`.
     *
     * @return self Cloned, modified instance.
     * @throws \Error If an invalid minimum version is given.
     */
    public function withMinimumVersion(int $version): self
    {
        if ($version !== self::TLSv1_0 && $version !== self::TLSv1_1 && $version !== self::TLSv1_2) {
            throw new \Error('Invalid minimum version, only TLSv1.0, TLSv1.1 or TLSv1.2 allowed');
        }

        $clone = clone $this;
        $clone->minVersion = $version;

        return $clone;
    }

    /**
     * Returns the minimum TLS version to negotiate.
     *
     * @return int
     */
    public function getMinimumVersion(): int
    {
        return $this->minVersion;
    }

    /**
     * Expected name of the peer.
     *
     * @param string|null $peerName
     *
     * @return self Cloned, modified instance.
     */
    public function withPeerName(string $peerName = null): self
    {
        $clone = clone $this;
        $clone->peerName = $peerName;

        return $clone;
    }

    /**
     * @return null|string Expected name of the peer or `null` if such an expectation doesn't exist.
     */
    public function getPeerName()
    {
        return $this->peerName;
    }

    /**
     * Enable peer verification.
     *
     * @return self Cloned, modified instance.
     */
    public function withPeerVerification(): self
    {
        $clone = clone $this;
        $clone->verifyPeer = true;

        return $clone;
    }

    /**
     * Disable peer verification, this is the default for servers.
     *
     * @return self Cloned, modified instance.
     */
    public function withoutPeerVerification(): self
    {
        $clone = clone $this;
        $clone->verifyPeer = false;

        return $clone;
    }

    /**
     * @return bool Whether peer verification is enabled.
     */
    public function hasPeerVerification(): bool
    {
        return $this->verifyPeer;
    }

    /**
     * Maximum chain length the peer might present including the certificates in the local trust store.
     *
     * @param int $verifyDepth Maximum length of the certificate chain.
     *
     * @return self Cloned, modified instance.
     */
    public function withVerificationDepth(int $verifyDepth): self
    {
        if ($verifyDepth < 0) {
            throw new \Error("Invalid verification depth ({$verifyDepth}), must be greater than or equal to 0");
        }

        $clone = clone $this;
        $clone->verifyDepth = $verifyDepth;

        return $clone;
    }

    /**
     * @return int Maximum length of the certificate chain.
     */
    public function getVerificationDepth(): int
    {
        return $this->verifyDepth;
    }

    /**
     * List of ciphers to negotiate, the server's order is always preferred.
     *
     * @param string|null $ciphers List of ciphers in OpenSSL's format (colon separated).
     *
     * @return self Cloned, modified instance.
     */
    public function withCiphers(string $ciphers = null): self
    {
        $clone = clone $this;
        $clone->ciphers = $ciphers;

        return $clone;
    }

    /**
     * @return string List of ciphers in OpenSSL's format (colon separated).
     */
    public function getCiphers(): string
    {
        return $this->ciphers ?? \OPENSSL_DEFAULT_STREAM_CIPHERS;
    }

    /**
     * CAFile to check for trusted certificates.
     *
     * @param string|null $cafile Path to the file or `null` to unset.
     *
     * @return self Cloned, modified instance.
     */
    public function withCaFile(string $cafile = null): self
    {
        $clone = clone $this;
        $clone->caFile = $cafile;

        return $clone;
    }

    /**
     * @return null|string Path to the file if one is set, otherwise `null`.
     */
    public function getCaFile()
    {
        return $this->caFile;
    }

    /**
     * CAPath to check for trusted certificates.
     *
     * @param string|null $capath Path to the file or `null` to unset.
     *
     * @return self Cloned, modified instance.
     */
    public function withCaPath(string $capath = null): self
    {
        $clone = clone $this;
        $clone->caPath = $capath;

        return $clone;
    }

    /**
     * @return null|string Path to the file if one is set, otherwise `null`.
     */
    public function getCaPath()
    {
        return $this->caPath;
    }

    /**
     * Capture the certificates sent by the peer.
     *
     * Note: This is the chain as sent by the peer, NOT the verified chain.
     *
     * @return self Cloned, modified instance.
     */
    public function withPeerCapturing(): self
    {
        $clone = clone $this;
        $clone->capturePeer = true;

        return $clone;
    }

    /**
     * Don't capture the certificates sent by the peer.
     *
     * @return self Cloned, modified instance.
     */
    public function withoutPeerCapturing(): self
    {
        $clone = clone $this;
        $clone->capturePeer = false;

        return $clone;
    }

    /**
     * @return bool Whether to capture the certificates sent by the peer.
     */
    public function hasPeerCapturing(): bool
    {
        return $this->capturePeer;
    }

    /**
     * Default certificate to use in case no SNI certificate matches.
     *
     * @param Certificate|null $defaultCertificate
     *
     * @return self Cloned, modified instance.
     */
    public function withDefaultCertificate(Certificate $defaultCertificate = null): self
    {
        $clone = clone $this;
        $clone->defaultCertificate = $defaultCertificate;

        return $clone;
    }

    /**
     * @return Certificate|null Default certificate to use in case no SNI certificate matches, or `null` if unset.
     */
    public function getDefaultCertificate()
    {
        return $this->defaultCertificate;
    }

    /**
     * Certificates to use for the given host names.
     *
     * @param array $certificates Must be a associative array mapping hostnames to certificate instances.
     *
     * @return self Cloned, modified instance.
     */
    public function withCertificates(array $certificates): self
    {
        foreach ($certificates as $key => $certificate) {
            if (!\is_string($key)) {
                throw new \TypeError('Expected an array mapping domain names to Certificate instances');
            }

            if (!$certificate instanceof Certificate) {
                throw new \TypeError('Expected an array of Certificate instances');
            }

            if (\PHP_VERSION_ID < 70200 && $certificate->getCertFile() !== $certificate->getKeyFile()) {
                throw new \Error(
                    'Different files for cert and key are not supported on this version of PHP. ' .
                    'Please upgrade to PHP 7.2 or later.'
                );
            }
        }

        $clone = clone $this;
        $clone->certificates = $certificates;

        return $clone;
    }

    /**
     * @return array Associative array mapping hostnames to certificate instances.
     */
    public function getCertificates(): array
    {
        return $this->certificates;
    }

    /**
     * Security level to use.
     *
     * Requires OpenSSL 1.1.0 or higher.
     *
     * @param int $level Must be between 0 and 5.
     *
     * @return self Cloned, modified instance.
     */
    public function withSecurityLevel(int $level): self
    {
        // See https://www.openssl.org/docs/manmaster/man3/SSL_CTX_set_security_level.html
        // Level 2 is not recommended, because of SHA-1 by that document,
        // but SHA-1 should be phased out now on general internet use.
        // We therefore default to level 2.

        if ($level < 0 || $level > 5) {
            throw new \Error("Invalid security level ({$level}), must be between 0 and 5.");
        }

        if (\OPENSSL_VERSION_NUMBER < 0x10100000) {
            throw new \Error("Can't set a security level, as PHP is compiled with OpenSSL < 1.1.0.");
        }

        $clone = clone $this;
        $clone->securityLevel = $level;

        return $clone;
    }

    /**
     * @return int Security level between 0 and 5. Always 0 for OpenSSL < 1.1.0.
     */
    public function getSecurityLevel(): int
    {
        // 0 is equivalent to previous versions of OpenSSL and just does nothing
        if (\OPENSSL_VERSION_NUMBER < 0x10100000) {
            return 0;
        }

        return $this->securityLevel;
    }

    /**
     * Converts this TLS context into PHP's equivalent stream context array.
     *
     * @return array Stream context array compatible with PHP's streams.
     */
    public function toStreamContextArray(): array
    {
        $options = [
            'crypto_method' => $this->toStreamCryptoMethod(),
            'peer_name' => $this->peerName,
            'verify_peer' => $this->verifyPeer,
            'verify_peer_name' => $this->verifyPeer,
            'verify_depth' => $this->verifyDepth,
            'ciphers' => $this->ciphers ?? \OPENSSL_DEFAULT_STREAM_CIPHERS,
            'honor_cipher_order' => true,
            'single_dh_use' => true,
            'no_ticket' => true,
            'capture_peer_cert' => $this->capturePeer,
            'capture_peer_chain' => $this->capturePeer,
        ];

        if ($this->defaultCertificate !== null) {
            $options['local_cert'] = $this->defaultCertificate->getCertFile();

            if ($this->defaultCertificate->getCertFile() !== $this->defaultCertificate->getKeyFile()) {
                $options['local_pk'] = $this->defaultCertificate->getKeyFile();
            }
        }

        if ($this->certificates) {
            $options['SNI_server_certs'] = \array_map(function (Certificate $certificate) {
                if ($certificate->getCertFile() === $certificate->getKeyFile()) {
                    return $certificate->getCertFile();
                }

                return [
                    'local_cert' => $certificate->getCertFile(),
                    'local_pk' => $certificate->getKeyFile(),
                ];
            }, $this->certificates);
        }

        if ($this->caFile !== null) {
            $options['cafile'] = $this->caFile;
        }

        if ($this->caPath !== null) {
            $options['capath'] = $this->caPath;
        }

        if (\OPENSSL_VERSION_NUMBER >= 0x10100000) {
            $options['security_level'] = $this->securityLevel;
        }

        return ['ssl' => $options];
    }

    /**
     * @return int Crypto method compatible with PHP's streams.
     */
    public function toStreamCryptoMethod(): int
    {
        switch ($this->minVersion) {
            case self::TLSv1_0:
                return self::TLSv1_0 | self::TLSv1_1 | self::TLSv1_2;

            case self::TLSv1_1:
                return self::TLSv1_1 | self::TLSv1_2;

            case self::TLSv1_2:
                return self::TLSv1_2;

            default:
                throw new \RuntimeException('Unknown minimum TLS version: ' . $this->minVersion);
        }
    }
}
