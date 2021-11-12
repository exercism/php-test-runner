FROM php:8.0.12-cli-bullseye

# Install SSL ca certificates
RUN apt-get update && \
  apt-get install curl bash -y

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
  install-php-extensions ds-1.3.0 intl

# Install Node
RUN curl -fsSL https://deb.nodesource.com/setup_14.x | bash - && \
  apt-get install -y nodejs && \
  npm install -g npm@7.5.4

# Create appuser
RUN useradd -ms /bin/bash appuser

# Install PHPUnit
WORKDIR /opt/test-runner/bin
RUN curl -Lo phpunit-9.phar https://phar.phpunit.de/phpunit-9.phar && \
  chmod +x phpunit-9.phar

WORKDIR /opt/test-runner
COPY . .

WORKDIR /opt/test-runner/junit-to-json
RUN npm install --unsafe-perm

WORKDIR /opt/test-runner
USER appuser

ENTRYPOINT ["/opt/test-runner/bin/run.sh"]