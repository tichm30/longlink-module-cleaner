# Longlink Module Cleaner 0.1.0k-CX Update Log

Date: 2026-06-16
Branch: claude/funny-hawking-module-cleaner

## Changes

- Replaced module-local `form-stack` spacing with the host `stack` utility so Cleaner views consume the canonical ERP design system.
- Applied the full host mobile-card table wrapper contract to Cleaner tables with `data-table-wrap is-card-table`.
- Pinned the module release to host minimum version `0.7.9j-CX`.
- Updated architecture tests to keep Cleaner on the host spacing and mobile-card table contracts.

## Verification

- `php tests/run-module-tests.php`
- `git diff --check`
- `bash packaging/build-module-zip.sh`

