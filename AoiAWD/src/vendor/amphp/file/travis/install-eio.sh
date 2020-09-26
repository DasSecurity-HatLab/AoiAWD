#!/usr/bin/env bash

git clone https://github.com/rosmanov/pecl-eio
pushd pecl-eio;
phpize;
./configure;
make;
make install;
popd;
echo "extension=eio.so" >> "$(php -r 'echo php_ini_loaded_file();')";
