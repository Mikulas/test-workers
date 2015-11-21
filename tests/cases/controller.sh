#!/usr/bin/env bash
set -uo pipefail
IFS=$'\n\t'
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}" )" && pwd)"
ROOT="$(dirname "$(dirname "$DIR")")"

function assert {
	if [[ "$1" != "$2" ]]; then
		echo -e "\n\e[31m$3\e[0m" >&2
		echo -e "Output was\n$4" >&2
		exit 1
	fi
	echo -n '.'
}

function controller {
	phpdbg -qrr "$ROOT"/bin/test-workers "$@"
}

OUTPUT="$(controller "$ROOT"/tests/examples/success.php)"
assert "$?" "0" "Exit code with failed test should be zero" "$OUTPUT"

OUTPUT="$(controller "$ROOT"/tests/examples/failure.php)"
assert "$?" "1" "Exit code with failed test should be non-zero" "$OUTPUT"

printf "\n"
