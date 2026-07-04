#!/usr/bin/env bash
# Run every smoke suite with assertions enabled. Exits non-zero on the
# first failing suite. Usage: tests/run.sh [php-binary]
set -u

PHP_BIN="${1:-php}"
DIR="$(cd "$(dirname "$0")" && pwd)"
FAIL=0

for suite in "$DIR"/*-smoke.php; do
	name="$(basename "$suite")"
	printf '%-28s' "$name:"

	if output="$("$PHP_BIN" -d zend.assertions=1 -d assert.exception=1 "$suite" 2>&1)"; then
		echo "$output" | tail -1
	else
		echo "FAIL"
		echo "$output" | tail -12
		FAIL=1
	fi
done

exit $FAIL
