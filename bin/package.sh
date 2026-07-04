#!/usr/bin/env bash
# Build the distributable plugin zip: everything except the dev files
# listed in .distignore, then verify no development artifact shipped.
# The build FAILS if a forbidden file reaches the zip.
# Usage: bin/package.sh [output-dir]
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="${1:-$ROOT/build}"
SLUG="zihad-travel-cms"
VERSION="$(grep -m1 " \* Version:" "$ROOT/$SLUG.php" | awk '{print $3}')"
STAGE="$OUT/$SLUG"
ZIP="$OUT/$SLUG-$VERSION.zip"

echo "Packaging $SLUG $VERSION"
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

( cd "$OUT" && zip -qr "$SLUG-$VERSION.zip" "$SLUG" )
rm -rf "$STAGE"

# Verification gate: no development artifact may ship.
FORBIDDEN='(\.DS_Store|Thumbs\.db|\.claude|\.git|\.gitkeep|node_modules|/tests/|/docs/|/bin/|\.map$|composer\.(json|lock)|phpcs\.xml|phpstan\.neon|\.github|\.distignore)'
if unzip -l "$ZIP" | awk '{print $4}' | grep -qE "$FORBIDDEN"; then
	echo "BUILD FAILED — development artifacts found in the zip:" >&2
	unzip -l "$ZIP" | awk '{print $4}' | grep -E "$FORBIDDEN" >&2
	rm -f "$ZIP"
	exit 1
fi

echo "Built $ZIP (verified clean)"
unzip -l "$ZIP" | tail -2
