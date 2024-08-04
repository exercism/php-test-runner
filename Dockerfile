FROM php:8.3.10-cli-alpine3.20 AS build

RUN apk update \
    && apk add --no-cache ca-certificates curl jo zip unzip

WORKDIR /usr/local/bin

RUN curl -L -o install-php-extensions \
    https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
    && chmod +x install-php-extensions \
    && install-php-extensions intl

COPY --from=composer:2.7.7 /usr/bin/composer /usr/local/bin/composer
RUN php --modules \
    && composer --version

FROM php:8.3.10-cli-alpine3.20 AS runtime

COPY --from=build /usr/bin/jo /usr/bin/jo
COPY --from=build /usr/local/lib/php/extensions /usr/local/lib/php/extensions

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && apk add --no-cache bash \
    && adduser -Ds /bin/bash appuser

WORKDIR /opt/test-runner
COPY . .
# composer warns about missing a "root version" to resolve dependencies. Fake to stop warning
# RUN COMPOSER_ROOT_VERSION=1.0.0 composer install --no-interaction --no-dev

USER appuser

RUN ls -la

ENTRYPOINT ["/opt/test-runner/bin/run.sh"]
