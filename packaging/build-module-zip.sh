#!/usr/bin/env bash
# Build the installable addon zip from module/.
#
# Output: releases/longlink-module-cleaner-<version>.zip + .sha256 sidecar.
# The zip's internal version (module/module.json -> version) must equal the
# filename version - the host portal's pre-zip gate will reject mismatches.

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [ ! -f module/module.json ]; then
    echo "FATAL: module/module.json not found at $ROOT/module/" >&2
    exit 1
fi

VERSION="$(php -r 'echo json_decode(file_get_contents("module/module.json"), true)["version"] ?? "";')"
if [ -z "$VERSION" ]; then
    echo "FATAL: module/module.json has no 'version' field" >&2
    exit 1
fi

ZIP_NAME="longlink-module-cleaner-${VERSION}.zip"
BUILD_ROOT="$ROOT/.build/module_cleaner_zip"
ZIP_ROOT="$BUILD_ROOT/module_cleaner"
mkdir -p releases
rm -rf "$BUILD_ROOT"
mkdir -p "$ZIP_ROOT"
rm -f "releases/${ZIP_NAME}" "releases/${ZIP_NAME}.sha256"

cp -R module/. "$ZIP_ROOT/"

# Hygiene exclusions (mirrors the host's release builder)
(
    cd "$BUILD_ROOT"
    COPYFILE_DISABLE=1 zip -X -qr "$ROOT/releases/${ZIP_NAME}" module_cleaner/ \
    -x "*/.DS_Store" \
    -x ".DS_Store" \
    -x "*/__MACOSX/*" \
    -x "*/._*" \
    -x "*/node_modules/*" \
    -x "*/.git/*" \
    -x "*/.env" \
    -x "*/storage/*"
)

rm -rf "$BUILD_ROOT"

bash "$ROOT/packaging/verify-zip-hygiene.sh" "releases/${ZIP_NAME}"

# SHA-256 sidecar
( cd releases && shasum -a 256 "$ZIP_NAME" > "${ZIP_NAME}.sha256" )

echo "Built: releases/${ZIP_NAME}"
cat "releases/${ZIP_NAME}.sha256"
