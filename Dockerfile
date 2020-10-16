FROM node:6 as frontend
COPY ./Frontend /usr/src/Frontend
WORKDIR /usr/src/Frontend
RUN npm install && \
    npm run build

FROM php:7.2-cli
COPY ./AoiAWD /usr/src/AoiAWD
WORKDIR /usr/src/AoiAWD
RUN pecl install mongodb && \
    docker-php-ext-enable mongodb && \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    echo "phar.readonly=Off" > "$PHP_INI_DIR/conf.d/phar.ini" && \
    rm -rf ./src/public/static/*
COPY --from=frontend /usr/src/Frontend/dist/* ./src/public/static/
RUN mv ./src/public/static/index.html ./src/public/index.html 
RUN php ./compile.php

ENTRYPOINT [ "php", "./aoiawd.phar" ]