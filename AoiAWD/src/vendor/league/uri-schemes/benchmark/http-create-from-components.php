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
require __DIR__.'/../vendor/autoload.php';

$components = [
    'scheme' => 'https',
    'host' => 'uri.thephpleague.com',
    'path' => '/5.0',
];
for ($i = 0; $i < 100000; $i++) {
    League\Uri\Http::createFromComponents($components);
}
