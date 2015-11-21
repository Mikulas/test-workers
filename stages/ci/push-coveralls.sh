#!/usr/bin/env bash

if [[ "$TRAVIS_PHP_VERSION" != "7.0" ]]; then
	exit 0
fi

composer require satooshi/php-coveralls --prefer-dist --no-progress --no-interaction
php vendor/bin/coveralls
