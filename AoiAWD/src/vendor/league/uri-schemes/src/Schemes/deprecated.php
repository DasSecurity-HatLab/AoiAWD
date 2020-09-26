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

namespace League\Uri\Schemes;

use League\Uri as LeagueUri;
use function class_alias;
use function class_exists;

class_alias(LeagueUri\AbstractUri::class, AbstractUri::class);
if (!class_exists(AbstractUri::class)) {
    /**
     * @deprecated use instead {@link LeagueUri\AbstractUri}
     */
    class AbstractUri
    {
    }
}

class_alias(LeagueUri\Data::class, Data::class);
if (!class_exists(Data::class)) {
    /**
     * @deprecated use instead {@link LeagueUri\Data}
     */
    class Data
    {
    }
}

class_alias(LeagueUri\File::class, File::class);
if (!class_exists(File::class)) {
    /**
     * @deprecated use instead {@link LeagueUri\File}
     */
    class File
    {
    }
}

class_alias(LeagueUri\Ftp::class, Ftp::class);
if (!class_exists(Ftp::class)) {
    /**
     * @deprecated use instead {@link LeagueUri\Ftp}
     */
    class Ftp
    {
    }
}

class_alias(LeagueUri\Http::class, Http::class);
if (!class_exists(Http::class)) {
    /**
     * @deprecated use instead {@link LeagueUri\Http}
     */
    class Http
    {
    }
}

class_alias(LeagueUri\Uri::class, Uri::class);
if (!class_exists(Uri::class)) {
    /**
     * @deprecated use instead {@link LeagueUri\Uri}
     */
    class Uri
    {
    }
}

class_alias(LeagueUri\UriException::class, UriException::class);
if (!class_exists(UriException::class)) {
    /**
     * @deprecated use instead {@link LeagueUri\UriException}
     */
    class UriException
    {
    }
}

class_alias(LeagueUri\Ws::class, Ws::class);
if (!class_exists(Ws::class)) {
    /**
     * @deprecated use instead {@link LeagueUri\Ws}
     */
    class Ws
    {
    }
}
