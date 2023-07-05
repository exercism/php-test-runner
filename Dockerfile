FROM php:8.2.7-cli-bookworm

# Install SSL ca certificates
RUN apt-get update && \
  apt-get install curl bash jo -y

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
  install-php-extensions ds-1.4.0 intl

COPY --from=composer:2.5.8 /usr/bin/composer /usr/local/bin/composer

# Create appuser
RUN useradd -ms /bin/bash appuser

# Install PHPUnit
WORKDIR /opt/test-runner/bin
RUN curl -Lo phpunit-9.phar https://phar.phpunit.de/phpunit-9.phar && \
  chmod +x phpunit-9.phar

WORKDIR /opt/test-runner
COPY . .

# Install the deps for test-reflector
WORKDIR /opt/test-runner/junit-handler
RUN composer install --no-interaction 

WORKDIR /opt/test-runner
USER appuser

ENTRYPOINT ["/opt/test-runner/bin/run.sh"]
