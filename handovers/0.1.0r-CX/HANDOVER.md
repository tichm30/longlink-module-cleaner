# Module Cleaner 0.1.0r-CX Handover

## Scope

- Converts Module Cleaner from a review-only surface into a persisted coordination module for cleanup planning.
- Adds module-owned coordination tables for cleanup plans, backup sets, quarantine records, orphan candidates, dependency edges, and self-destruct checks.
- Persists dry-run plans, backup evidence, dependency graph rows, self-destruct guard outcomes, orphan review candidates, and quarantine evidence.
- Adds a quarantine-evidence action that copies module files/storage/package artifacts without deleting source data.
- Adds backup-set restore-plan preparation so restore evidence can be reviewed before any cleanup is executed.
- Keeps destructive execution delegated to the host purge primitive with `backup_confirmed=true`.

## Artifacts

- `longlink-module-cleaner-0.1.0r-CX.zip`
- `longlink-module-cleaner-0.1.0r-CX.zip.sha256`

## Verification

- `find module/src module/database tests -name '*.php' -print0 | xargs -0 -n1 php -l` - no syntax errors.
- `php tests/run-module-tests.php` - all Module Cleaner tests passed.
- Release ZIP SHA256: `b641d323c3c7c76d9bea0b121674df34e92bc600e450a5a40eb7cf1f0a0317e0`

## Runtime Proof Status

This release is ready for host-shelf runtime verification. The module package now contains real persistence surfaces for Module Cleaner coordination, but final PASS for the global repair checklist still requires Apache/MySQL install/render evidence and proof that persisted plan, backup, quarantine, orphan, dependency, and self-destruct rows survive reload.
