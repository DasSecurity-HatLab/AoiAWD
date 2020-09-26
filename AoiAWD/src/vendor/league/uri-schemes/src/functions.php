<?php

/**
 * League.Uri (http://uri.thephpleague.com).
 *
 * @package    League\Uri
 * @subpackage League\Uri\Schemes
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license    https://github.com/thephpleague/uri-schemes/blob/master/LICENSE (MIT License)
 * @version    1.2.1
 * @link       https://github.com/thephpleague/uri-schemes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Uri;

use League\Uri\Interfaces\Uri as DeprecatedLeagueUriInterface;
use Psr\Http\Message\UriInterface as Psr7UriInterface;

/**
 * Create a new URI optionally according to
 * a base URI object.
 *
 * @see Uri\Factory::__construct
 * @see Uri\Factory::create
 *
 * @param  null|mixed                                                 $base_uri
 * @return Psr7UriInterface|DeprecatedLeagueUriInterface|UriInterface
 */
function create(string $uri, $base_uri = null)
{
    static $factory;

    $factory = $factory ?? new Factory();

    return $factory->create($uri, $base_uri);
}
