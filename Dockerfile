FROM php:8.3-alpine as base

WORKDIR /srv/app
RUN addgroup -g "1000" -S php \
  && adduser --system --gecos "" --ingroup "php" --uid "1000" php

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
  && install-php-extensions \
    @composer \
    apcu \
    sockets \
    zip

FROM base as local-dev
RUN apk add --no-cache \
    bash \
    git \
 && install-php-extensions \
    ast \
    xdebug-stable
USER php
