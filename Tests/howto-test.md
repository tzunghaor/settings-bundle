# HOWTO run tests locally

## Download composer

1. Create var directory (it is gitignored)
2. Download https://getcomposer.org/installer to var/composer_installer
3. run composer installer `./var/composer_installer --install-dir=./var`

## Build custom docker images for testing
 
* `docker build -t tzunghaor:php7.4-cli Tests/Resources/docker/php7.4-cli`
* `docker build -t tzunghaor:php8.0-cli Tests/Resources/docker/php8.0-cli`
* `docker build -t tzunghaor:php8.1-cli Tests/Resources/docker/php8.1-cli`

## Run tests in different environments

Install composer requirements with lowest and highest versions for each supported PHP version and run tests.

* `docker run --rm -it -v "$PWD":/app -w /app  tzunghaor:php<version>-cli var/composer.phar update --prefer-lowest && \
   docker run --rm -it -v "$PWD":/app -w /app  tzunghaor:php<version>-cli vendor/bin/phpunit`
* `docker run --rm -it -v "$PWD":/app -w /app  tzunghaor:php<version>-cli var/composer.phar update && \
   docker run --rm -it -v "$PWD":/app -w /app  tzunghaor:php<version>-cli vendor/bin/phpunit`
