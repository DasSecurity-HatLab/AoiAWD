<?php

/**
 * League.Uri (http://uri.thephpleague.com/parser).
 *
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license https://github.com/thephpleague/uri-parser/blob/master/LICENSE (MIT License)
 * @version 1.4.1
 * @link    https://uri.thephpleague.com/parser/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Uri;

/**
 * An exception thrown if the IDN support is missing or
 * the ICU is not at least version 4.6.
 *
 * @see     https://tools.ietf.org/html/rfc3986
 * @package League\Uri
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since   1.4.0
 */
class MissingIdnSupport extends Exception
{
}
