FROM php:7.2-cli

COPY ./Frontend /usr/src/Frontend
WORKDIR /usr/src/Frontend
RUN apt-get update && \
    apt-get install -y npm && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*
RUN npm install && \
    npm run build

COPY ./AoiAWD /usr/src/AoiAWD
WORKDIR /usr/src/AoiAWD
RUN pecl install mongodb && \
    docker-php-ext-enable mongodb && \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    echo "phar.readonly=Off" > "$PHP_INI_DIR/conf.d/phar.ini"
RUN cp -r /usr/src/Frontend/dist/* ./src/public && \
    php compile.php

CMD [ "php", "./aoiawd.phar" ]