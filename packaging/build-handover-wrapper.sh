#!/usr/bin/env bash
# Build an optional handover-folder wrapper zip without macOS metadata.

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"

if [ "$#" -lt 1 ]; then
    echo "Usage: bash packaging/build-handover-wrapper.sh <handover-dir> [output.zip]" >&2
    exit 1
fi

HANDOVER_DIR="$1"
if [[ "$HANDOVER_DIR" != /* ]]; then
    HANDOVER_DIR="$ROOT/$HANDOVER_DIR"
fi

if [ ! -d "$HANDOVER_DIR" ]; then
    echo "FATAL: handover directory not found: $HANDOVER_DIR" >&2
    exit 1
fi

OUTPUT="${2:-${HANDOVER_DIR%/}.zip}"
if [[ "$OUTPUT" != /* ]]; then
    OUTPUT="$ROOT/$OUTPUT"
fi

PARENT="$(cd "$(dirname "$HANDOVER_DIR")" && pwd)"
BASE="$(basename "$HANDOVER_DIR")"

mkdir -p "$(dirname "$OUTPUT")"
rm -f "$OUTPUT" "$OUTPUT.sha256"

(
    cd "$PARENT"
    COPYFILE_DISABLE=1 zip -X -qr "$OUTPUT" "$BASE" \
        -x "*/.DS_Store" \
        -x ".DS_Store" \
        -x "*/__MACOSX/*" \
        -x "*/._*" \
        -x "*/node_modules/*" \
        -x "*/.git/*" \
        -x "*/.env"
)

bash "$ROOT/packaging/verify-zip-hygiene.sh" "$OUTPUT"
( cd "$(dirname "$OUTPUT")" && shasum -a 256 "$(basename "$OUTPUT")" > "$(basename "$OUTPUT").sha256" )

echo "Built: $OUTPUT"
cat "$OUTPUT.sha256"
