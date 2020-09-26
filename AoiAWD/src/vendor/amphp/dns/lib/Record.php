<?php

namespace Amp\Dns;

use LibDNS\Records\ResourceQTypes;
use LibDNS\Records\ResourceTypes;

final class Record
{
    const A = ResourceTypes::A;
    const AAAA = ResourceTypes::AAAA;
    const AFSDB = ResourceTypes::AFSDB;
    // const APL = ResourceTypes::APL;
    const CAA = ResourceTypes::CAA;
    const CERT = ResourceTypes::CERT;
    const CNAME = ResourceTypes::CNAME;
    const DHCID = ResourceTypes::DHCID;
    const DLV = ResourceTypes::DLV;
    const DNAME = ResourceTypes::DNAME;
    const DNSKEY = ResourceTypes::DNSKEY;
    const DS = ResourceTypes::DS;
    const HINFO = ResourceTypes::HINFO;
    // const HIP = ResourceTypes::HIP;
    // const IPSECKEY = ResourceTypes::IPSECKEY;
    const KEY = ResourceTypes::KEY;
    const KX = ResourceTypes::KX;
    const ISDN = ResourceTypes::ISDN;
    const LOC = ResourceTypes::LOC;
    const MB = ResourceTypes::MB;
    const MD = ResourceTypes::MD;
    const MF = ResourceTypes::MF;
    const MG = ResourceTypes::MG;
    const MINFO = ResourceTypes::MINFO;
    const MR = ResourceTypes::MR;
    const MX = ResourceTypes::MX;
    const NAPTR = ResourceTypes::NAPTR;
    const NS = ResourceTypes::NS;
    // const NSEC = ResourceTypes::NSEC;
    // const NSEC3 = ResourceTypes::NSEC3;
    // const NSEC3PARAM = ResourceTypes::NSEC3PARAM;
    const NULL = ResourceTypes::NULL;
    const PTR = ResourceTypes::PTR;
    const RP = ResourceTypes::RP;
    // const RRSIG = ResourceTypes::RRSIG;
    const RT = ResourceTypes::RT;
    const SIG = ResourceTypes::SIG;
    const SOA = ResourceTypes::SOA;
    const SPF = ResourceTypes::SPF;
    const SRV = ResourceTypes::SRV;
    const TXT = ResourceTypes::TXT;
    const WKS = ResourceTypes::WKS;
    const X25 = ResourceTypes::X25;

    const AXFR = ResourceQTypes::AXFR;
    const MAILB = ResourceQTypes::MAILB;
    const MAILA = ResourceQTypes::MAILA;
    const ALL = ResourceQTypes::ALL;

    private $value;
    private $type;
    private $ttl;

    public function __construct(string $value, int $type, int $ttl = null)
    {
        $this->value = $value;
        $this->type = $type;
        $this->ttl = $ttl;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * Converts an record type integer back into its name as defined in this class.
     *
     * Returns "unknown (<type>)" in case a name for this record is not known.
     *
     * @param int $type Record type as integer.
     *
     * @return string Name of the constant for this record in this class.
     */
    public static function getName(int $type): string
    {
        static $types;

        if (0 > $type || 0xffff < $type) {
            $message = \sprintf('%d does not correspond to a valid record type (must be between 0 and 65535).', $type);
            throw new \Error($message);
        }

        if ($types === null) {
            $types = \array_flip(
                (new \ReflectionClass(self::class))
                    ->getConstants()
            );
        }

        return $types[$type] ?? "unknown ({$type})";
    }
}
