#!/bin/bash

PHP_VERSION=${PHP_VERSION:-8.5}
PHP_UNIT_ARGS=${PHP_UNIT_ARGS:-}
COMPOSER_ARGS=${COMPOSER_ARGS:-}

# update composer dependencies
docker run --rm -it -v "$PWD":/app -v ~/.cache/composer:/root/.composer/cache -w /app tzunghaor:php${PHP_VERSION} composer update  ${COMPOSER_ARGS} && \

  # run tests with debugging support
  docker run --rm -it -v "$PWD":/app -w /app -e PHP_IDE_CONFIG="serverName=docker" -v ./../phpunit:/phpunit tzunghaor:php${PHP_VERSION} \
    php -d memory_limit=512M /phpunit/phpunit-9.6.34.phar ${PHP_UNIT_ARGS}


