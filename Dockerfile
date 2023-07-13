FROM php:8.2.7-cli-bookworm

# Install SSL ca certificates
RUN apt-get update && \
  apt-get install curl bash jo git -y zip unzip && \
  apt-get purge --auto-remove && \
  apt-get clean && \
  rm -rf /var/lib/apt/lists/*

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN curl -Lo /usr/local/bin/install-php-extensions https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions && \
  curl -Lo /usr/local/bin/composer https://getcomposer.org/download/2.5.8/composer.phar && \
  chmod +x /usr/local/bin/install-php-extensions && \
  chmod +x /usr/local/bin/composer && \
  install-php-extensions ds-1.4.0 intl

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
