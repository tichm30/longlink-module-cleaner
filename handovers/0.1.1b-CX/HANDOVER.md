# 0.1.1b-CX Package Harness Repair

- ZIP: releases/longlink-module-cleaner-0.1.1b-CX.zip
- SHA-256: 5c3f4adc39f82ae48304127df6ee7a4ff64cfb2db92cea51ba21fc3592918f68
- Exact extracted archive: 5 test files passed, one explicit outer-ZIP skip, zero failures. Outer ZIP verification is separate.
- Evidence: ERP handovers/flt018-batch6-proof/ and FLT018_BATCH6_PROGRESS_2026-09-11.md.

Module runtime code and migrations are unchanged by this harness repair.

Real-host dependencies, package-root paths and fail-closed execution are covered. Native database tests use disposable MariaDB where present. This is not full browser CRUD, production activation, remote CI success or FLT-018 closure.

CI requires read-only LONGLINK_HOST_READ_TOKEN access to the ERP dependency checkout; no secret was provisioned here.

## Build Run Ledger
- Module packager invocations: 1.
- Full host builder runs: 0.
- Builder restarts after failure: 0.
- The first new archive passed. No archive replacement was required.
- I did NOT restart the full builder per failure.
