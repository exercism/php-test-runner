FROM php:8.2.7-cli-bookworm AS build

RUN apt-get update && \
  apt-get install curl bash jo -y zip unzip && \
  apt-get purge --auto-remove && \
  apt-get clean && \
  rm -rf /var/lib/apt/lists/*

RUN curl -L -o /usr/local/bin/install-php-extensions https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions && \
  chmod +x /usr/local/bin/install-php-extensions && \
  install-php-extensions ds-1.4.0 intl

RUN curl -L -o /usr/local/bin/phpunit-9.phar https://phar.phpunit.de/phpunit-9.phar && \
  chmod +x /usr/local/bin/phpunit-9.phar

WORKDIR /usr/local/bin/junit-handler/
COPY --from=composer:2.5.8 /usr/bin/composer /usr/local/bin/composer
COPY junit-handler/ .
RUN composer install --no-interaction 

FROM php:8.2.7-cli-bookworm AS runtime

COPY --from=build /usr/bin/jo /usr/bin/jo
COPY --from=build /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=build /usr/local/bin/phpunit-9.phar /opt/test-runner/bin/phpunit-9.phar
COPY --from=build /usr/local/bin/junit-handler /opt/test-runner/junit-handler

# Create appuser
RUN useradd -ms /bin/bash appuser

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /opt/test-runner
COPY . .

USER appuser

ENTRYPOINT ["/opt/test-runner/bin/run.sh"]
