<?php

/**
 * League.Uri (http://uri.thephpleague.com).
 *
 * @package    League\Uri
 * @subpackage League\Uri\Interfaces
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @copyright  2016 Ignace Nyamagana Butera
 * @license    https://github.com/thephpleague/uri-interfaces/blob/master/LICENSE (MIT License)
 * @version    1.1.0
 * @link       https://github.com/thephpleague/uri-interfaces/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace League\Uri\Interfaces;

use League\Uri\UriInterface;

/**
 * Value object representing a URI.
 *
 * Instances of this interface are considered immutable; all methods that
 * might change state MUST be implemented such that they retain the internal
 * state of the current instance and return an instance that contains the
 * changed state.
 *
 * @deprecated 1.1.0 will be removed in the next major point release
 * @see        League\Uri\UriInterface
 *
 * @package    League\Uri
 * @subpackage League\Uri\Interfaces
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since      1.0.0
 */
interface Uri extends UriInterface
{
}
