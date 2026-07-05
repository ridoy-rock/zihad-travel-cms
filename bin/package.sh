#!/usr/bin/env bash
# Build a distributable plugin zip for one distribution channel:
#
#   wporg  (default)  WordPress.org build — the Update URI header is
#                     stripped from the staged main file and the gate
#                     additionally rejects updater/licensing artifacts.
#                     Output: <slug>-<version>-wporg.zip
#   pro               Self-hosted / development build — byte-identical
#                     to the source tree minus .distignore, exactly as
#                     this script always produced.
#                     Output: <slug>-<version>.zip
#
# Both channels stage everything except the dev files listed in
# .distignore, then verify no development artifact shipped. The build
# FAILS if a forbidden file (or, for wporg, a forbidden pattern)
# reaches the zip.
#
# Usage: bin/package.sh [--channel=wporg|pro] [output-dir]
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CHANNEL="wporg"
OUT=""

for arg in "$@"; do
	case "$arg" in
		--channel=*) CHANNEL="${arg#--channel=}" ;;
		*) OUT="$arg" ;;
	esac
done

OUT="${OUT:-$ROOT/build}"

if [[ "$CHANNEL" != "wporg" && "$CHANNEL" != "pro" ]]; then
	echo "Unknown channel '$CHANNEL' — use --channel=wporg or --channel=pro." >&2
	exit 1
fi

SLUG="zihad-travel-cms"
VERSION="$(grep -m1 " \* Version:" "$ROOT/$SLUG.php" | awk '{print $3}')"
STAGE="$OUT/$SLUG"

if [[ "$CHANNEL" == "wporg" ]]; then
	ZIP="$OUT/$SLUG-$VERSION-wporg.zip"
else
	ZIP="$OUT/$SLUG-$VERSION.zip"
fi

echo "Packaging $SLUG $VERSION [$CHANNEL]"
rm -rf "$STAGE" "$ZIP"
mkdir -p "$STAGE"

# Copy the tree minus .distignore entries.
EXCLUDES=()
while IFS= read -r line; do
	[[ -z "$line" || "$line" == \#* ]] && continue
	EXCLUDES+=( "--exclude=$line" )
done < "$ROOT/.distignore"

rsync -a "${EXCLUDES[@]}" "$ROOT/" "$STAGE/"

# Belt and braces: Finder and editors recreate these at any time, so
# scrub the stage regardless of what .distignore caught.
find "$STAGE" \( -name '.DS_Store' -o -name 'Thumbs.db' -o -name '.gitkeep' -o -name '*.map' \) -delete
rm -rf "$STAGE/.claude" "$STAGE/.git"

if [[ "$CHANNEL" == "wporg" ]]; then
	# WordPress.org serves updates for hosted plugins itself; the
	# Update URI header is reserved for the self-hosted channel and
	# must not ship to the directory (plugin_updater_detected).
	awk '!/^ \* Update URI:/' "$STAGE/$SLUG.php" > "$STAGE/$SLUG.php.tmp"
	mv "$STAGE/$SLUG.php.tmp" "$STAGE/$SLUG.php"

	# Channel gate: nothing updater- or licensing-shaped may reach the
	# WordPress.org zip — this is what keeps future Pro plumbing from
	# leaking into the free directory build by accident.
	if grep -rq "Update URI:" "$STAGE"; then
		echo "BUILD FAILED [wporg] — 'Update URI:' present in the stage:" >&2
		grep -rl "Update URI:" "$STAGE" >&2
		rm -rf "$STAGE"
		exit 1
	fi

	if grep -rEiq "license_key|licence_key|plugin_?updater|update_client" "$STAGE"; then
		echo "BUILD FAILED [wporg] — updater/licensing artifact in the stage:" >&2
		grep -rEil "license_key|licence_key|plugin_?updater|update_client" "$STAGE" >&2
		rm -rf "$STAGE"
		exit 1
	fi

	if ! grep -q "^Stable tag:" "$STAGE/readme.txt" 2>/dev/null; then
		echo "BUILD FAILED [wporg] — readme.txt missing or has no 'Stable tag:' header." >&2
		rm -rf "$STAGE"
		exit 1
	fi
fi

( cd "$OUT" && zip -qr "$(basename "$ZIP")" "$SLUG" )
rm -rf "$STAGE"

# Verification gate: no development artifact may ship.
FORBIDDEN='(\.DS_Store|Thumbs\.db|\.claude|\.git|\.gitkeep|node_modules|/tests/|/docs/|/bin/|\.map$|composer\.(json|lock)|phpcs\.xml|phpstan\.neon|\.github|\.distignore)'
if unzip -l "$ZIP" | awk '{print $4}' | grep -qE "$FORBIDDEN"; then
	echo "BUILD FAILED — development artifacts found in the zip:" >&2
	unzip -l "$ZIP" | awk '{print $4}' | grep -E "$FORBIDDEN" >&2
	rm -f "$ZIP"
	exit 1
fi

echo "Built $ZIP (verified clean, channel: $CHANNEL)"
unzip -l "$ZIP" | tail -2
