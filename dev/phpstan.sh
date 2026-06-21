#!/bin/bash

PHP_VERSION=${PHP_VERSION:-8.5}

docker run --rm -it -v "$PWD":/app -w /app -e tzunghaor:php${PHP_VERSION} php /app/vendor/bin/phpstan --memory-limit=500M


