#!/usr/bin/env bash
# Build the distributable plugin zip: everything except the dev files
# listed in .distignore. Usage: bin/package.sh [output-dir]
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="${1:-$ROOT/build}"
SLUG="zihad-travel-cms"
VERSION="$(grep -m1 " \* Version:" "$ROOT/$SLUG.php" | awk '{print $3}')"
STAGE="$OUT/$SLUG"

echo "Packaging $SLUG $VERSION"
rm -rf "$STAGE" "$OUT/$SLUG-$VERSION.zip"
mkdir -p "$STAGE"

# Copy the tree minus .distignore entries.
EXCLUDES=()
while IFS= read -r line; do
	[[ -z "$line" || "$line" == \#* ]] && continue
	EXCLUDES+=( "--exclude=$line" )
done < "$ROOT/.distignore"

rsync -a "${EXCLUDES[@]}" "$ROOT/" "$STAGE/"

( cd "$OUT" && zip -qr "$SLUG-$VERSION.zip" "$SLUG" )
rm -rf "$STAGE"

echo "Built $OUT/$SLUG-$VERSION.zip"
unzip -l "$OUT/$SLUG-$VERSION.zip" | tail -2
