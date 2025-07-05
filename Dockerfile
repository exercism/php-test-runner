FROM php:8.4.10-cli-alpine3.22 AS build

RUN apk add --no-cache ca-certificates curl jo zip unzip

WORKDIR /usr/local/bin

RUN curl -L -o install-php-extensions \
    https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
    && chmod +x install-php-extensions \
    && install-php-extensions ds-^1@stable intl

COPY --from=composer:2.8.9 /usr/bin/composer /usr/local/bin/composer

WORKDIR /opt/test-runner
COPY . .
# composer warns about missing a "root version" to resolve dependencies. Fake to stop warning.
# composer warns about running as root. Silence it, we know what we are doing.
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && php --modules \
    && COMPOSER_ALLOW_SUPERUSER=1 \
        composer --version \
    && COMPOSER_ROOT_VERSION=1.0.0 \
        COMPOSER_ALLOW_SUPERUSER=1 \
        composer install --no-cache --no-dev --no-interaction --no-progress

FROM php:8.4.10-cli-alpine3.22 AS runtime

COPY --from=build /usr/bin/jo /usr/bin/jo
COPY --from=build /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=build /opt/test-runner /opt/test-runner

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && apk add --no-cache bash \
    && adduser -Ds /bin/bash appuser

WORKDIR /opt/test-runner

USER appuser

ENTRYPOINT ["/opt/test-runner/bin/run.sh"]
