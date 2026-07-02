# Module Cleaner 0.1.0q-CX Handover

## Scope

- Adds a `SampleDataProviderContract` implementation backed by the host `SeedsSampleDataTables` helper.
- Seeds a sample-owned Cleaner cleanup-log row for Gate 14 runtime install/purge coverage.
- Raises the host requirement to `0.7.12y-CX` and locks the provider contract in the standalone architecture test.

## Artifacts

- `longlink-module-cleaner-0.1.0q-CX.zip`
- `longlink-module-cleaner-0.1.0q-CX.zip.sha256`

## Verification

- `php -l module/src/ModuleCleanerModuleProvider.php` - no syntax errors.
- `php tests/run-module-tests.php` - Cleaner suite passed.
- Release ZIP SHA256: `44ec921d5be4ba4e0937e5a272cd1db690f80166492455f12ab21156ea33bdc9`

## Runtime Proof Status

This is a provider-source and ZIP checkpoint only. Do not mark the module repair checklist Gate 14/D row as PASS until the module is installed on the Apache/MySQL host and the runtime proof shows sample rows render, purge removes only sample-owned rows, and real rows survive.
