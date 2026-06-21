# HOWTO run tests locally

## Build custom php docker container

This container includes composer and its dependencies and it has xdebug. To build for e.g. PHP 8.5:

`PHP_VERSION=8.5 && docker build -t tzunghaor:php${PHP_VERSION} Tests/docker/php-cli --build-arg PHP_VERSION=${PHP_VERSION}`

## Run tests in different environments

Install composer requirements with lowest and highest versions for the selected PHP version and run tests.

* `PHP_VERSION=8.0 COMPOSER_ARGS=--prefer-lowest dev/test.sh`
* `PHP_VERSION=8.5 dev/test.sh`
