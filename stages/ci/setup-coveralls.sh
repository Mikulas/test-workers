#!/usr/bin/env bash
set -uo pipefail
IFS=$'\n\t'
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}" )" && pwd)"

if [[ "$TRAVIS_PHP_VERSION" != "7.0" ]]; then
	exit 0
fi

composer require satooshi/php-coveralls --prefer-dist --no-progress --no-interaction
