FROM php:8.0.2-cli-buster

# Install SSL ca certificates
RUN apt-get update && \
  apt-get install curl bash -y

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