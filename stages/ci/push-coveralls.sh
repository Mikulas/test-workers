#!/usr/bin/env bash
set -uo pipefail
IFS=$'\n\t'
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}" )" && pwd)"
ROOT="$(dirname "$(dirname "$DIR")")"

if [[ "$TRAVIS_PHP_VERSION" != "7.0" ]]; then
	exit 0
fi

cd "$DIR"
php "$ROOT"/vendor/bin/coveralls --verbose --config=coveralls.yml
