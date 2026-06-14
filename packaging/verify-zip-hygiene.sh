#!/usr/bin/env bash
# Verify addon release or handover zips do not contain platform/runtime debris.

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [ "$#" -gt 0 ]; then
    zips=("$@")
else
    zips=()
    while IFS= read -r zip_path; do
        zips+=("$zip_path")
    done < <({ find releases -type f -name '*.zip'; find handovers -mindepth 2 -maxdepth 2 -type f -name '*.zip'; } 2>/dev/null | sort)
fi

if [ "${#zips[@]}" -eq 0 ]; then
    echo "No zip artifacts found to verify."
    exit 0
fi

status=0

for zip_path in "${zips[@]}"; do
    if [ ! -f "$zip_path" ]; then
        echo "FATAL: zip not found: $zip_path" >&2
        status=1
        continue
    fi

    bad_entries="$(unzip -l "$zip_path" | awk '{print $4}' | grep -E '(^|/)__MACOSX/|(^|/)\.DS_Store$|(^|/)\._|(^|/)\.git/|(^|/)node_modules/|(^|/)\.env$' || true)"

    if [ -n "$bad_entries" ]; then
        echo "FATAL: forbidden entries found in $zip_path:" >&2
        echo "$bad_entries" >&2
        status=1
    else
        echo "Hygiene OK: $zip_path"
    fi
done

exit "$status"
