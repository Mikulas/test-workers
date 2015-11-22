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


diff <(php "$ROOT"/tests/examples/with-setup.php) <(cat "$ROOT"/tests/fixtures/setup-raw.txt)
assert "$?" "0" "Running test with setup directly failed" ""

diff <(controller --setup="$ROOT"/tests/examples/setup.php "$ROOT"/tests/examples/with-setup.php "$ROOT"/tests/examples/with-setup.php | head -1 ) <(cat "$ROOT"/tests/fixtures/setup-preload.txt)
assert "$?" "0" "Running test with setup directly failed" ""

OUTPUT="$(controller --setup=foo tests/*)"
assert "$?" "1" "Setup file not found" "$OUTPUT"

printf "\n"
