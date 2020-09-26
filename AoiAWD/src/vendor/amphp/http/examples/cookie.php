<?php

use Amp\Http\Cookie\CookieAttributes;
use Amp\Http\Cookie\RequestCookie;
use Amp\Http\Cookie\ResponseCookie;

require __DIR__ . "/../vendor/autoload.php";

$attributes = CookieAttributes::default()->withSecure();
$cookie = new ResponseCookie("session", \bin2hex(\random_bytes(16)), $attributes);

\var_dump($cookie->getName());
\var_dump($cookie->getValue());
\var_dump($cookie->isHttpOnly());
\var_dump("set-cookie: " . $cookie);

$cookie = ResponseCookie::fromHeader($cookie);
$cookie = new RequestCookie("session", $cookie->getValue());

\var_dump($cookie->getName());
\var_dump($cookie->getValue());
\var_dump("cookie: " . $cookie);
