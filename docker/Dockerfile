FROM existenz/webstack:8.3 AS base

RUN apk -U upgrade && apk add --no-cache \
    tzdata \
    postgresql-client \
    php83 \
    php83-apcu \
    php83-cli \
    php83-ctype \
    php83-curl \
    php83-dom \
    php83-iconv \
    php83-json \
    php83-mbstring \
    php83-openssl \
    php83-pcntl \
    php83-pdo_pgsql \
    php83-phar \
    php83-session \
    php83-tokenizer \
    php83-xdebug \
    php83-xml \
    php83-zip \
    ;

RUN ln -s /usr/bin/php83 /usr/sbin/php

COPY --from=composer:latest --link /usr/bin/composer /usr/local/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

# make user match with typical debian/ubuntu setup
RUN sed -i 's/php:x:101:102:Linux User/php:x:1000:1000:Linux User/' /etc/passwd \
        && sed -i 's/php:x:102/php:x:1000/' /etc/group \
        && chown -R php:php /home/php
