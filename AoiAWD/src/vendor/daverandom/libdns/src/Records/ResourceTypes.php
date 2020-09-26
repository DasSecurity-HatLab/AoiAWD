<?php declare(strict_types=1);
/**
 * Enumeration of possible resource TYPE values
 *
 * PHP version 5.4
 *
 * @category LibDNS
 * @package Records
 * @author Chris Wright <https://github.com/DaveRandom>
 * @copyright Copyright (c) Chris Wright <https://github.com/DaveRandom>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @version 2.0.0
 */
namespace LibDNS\Records;

use \LibDNS\Enumeration;

/**
 * Enumeration of possible resource TYPE values
 *
 * @category LibDNS
 * @package Records
 * @author Chris Wright <https://github.com/DaveRandom>
 */
abstract class ResourceTypes extends Enumeration
{
    const A          = 1;
    const AAAA       = 28;
    const AFSDB      = 18;
//    const APL        = 42;
    const CAA        = 257;
    const CERT       = 37;
    const CNAME      = 5;
    const DHCID      = 49;
    const DLV        = 32769;
    const DNAME      = 39;
    const DNSKEY     = 48;
    const DS         = 43;
    const HINFO      = 13;
//    const HIP        = 55;
//    const IPSECKEY   = 45;
    const KEY        = 25;
    const KX         = 36;
    const ISDN       = 20;
    const LOC        = 29;
    const MB         = 7;
    const MD         = 3;
    const MF         = 4;
    const MG         = 8;
    const MINFO      = 14;
    const MR         = 9;
    const MX         = 15;
    const NAPTR      = 35;
    const NS         = 2;
//    const NSEC       = 47;
//    const NSEC3      = 50;
//    const NSEC3PARAM = 50;
    const NULL       = 10;
    const PTR        = 12;
    const RP         = 17;
//    const RRSIG      = 46;
    const RT         = 21;
    const SIG        = 24;
    const SOA        = 6;
    const SPF        = 99;
    const SRV        = 33;
    const TXT        = 16;
    const WKS        = 11;
    const X25        = 19;
}
