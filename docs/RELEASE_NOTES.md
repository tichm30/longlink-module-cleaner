# Longlink Module Cleaner Release Notes

## 0.1.0i-CX

- Renamed the packaged addon display name from `Longlink Module Cleaner` to `Module Cleaner` so the host Addon Modules list stays feature-first.
- Tightened the manifest description to describe the cleanup workflow without repeating host branding already shown by the shell.
- Pinned the module to host `0.7.9i-CX`, the stale Platform Tools duplicate-navigation cleanup baseline.

Host minimum: `0.7.9i-CX`.

## 0.1.0g-CX

- Replaced the Cleaner console button strip with the host Categories left rail and mobile Categories Menu links.
- Bumped the host minimum to consume the shared routed category-link component.

Host minimum: `0.7.9d-CX`.

## 0.1.0e-CX

- Fixed the live MariaDB/MySQL 500 on `/admin/module-cleaner` by renaming package inventory SQL aliases from reserved/ambiguous `rows` and `bytes` to `package_rows` and `package_bytes`.
- Preserved the existing UI output keys, so the dashboard still renders package residue as `rows` and `bytes`.
- Added regression coverage to prevent the reserved SQL aliases returning.

Host minimum: `0.7.8t-CX`.

## 0.1.0d-CX

- Fixed the `/admin/module-cleaner` 500 by replacing the missing `x-app-layout` wrapper with the host `x-layouts.app-shell` component.
- Reworked the cleaner admin surface to use host cards, data tables, pills, empty states, form actions, and token-backed buttons.
- Hardened package residue inventory so hosts without an `addon_module_packages.module_key` column do not trigger a cleanup screen failure.
- Added regression coverage for the host shell and shared UI primitive contract.

Host minimum: `0.7.8t-CX`.

## 0.1.0c-CX

- Added protected-module handling for first-party and safety-critical modules, with dry-run planning disabled before host purge primitives are called.
- Deepened the residue inventory for packages and module files alongside tables, settings, permissions, navigation, and storage.
- Added queryable cleanup audit fields for navigation, storage, packages, and module files.
- Updated the cleaner UI with residue details, protected-module reasons, and clearer v1 ownership snapshot wording.
- Kept `module_cleaner.purge` reserved for a future host-guarded purge screen and kept direct destructive cleanup out of the module.
- Added cleaner safety contract regression tests.

Host minimum: `0.7.8t-CX`.

## 0.1.0b-CX

- Populated the standalone `module_cleaner` addon module.
- Added the runtime provider, module-prefixed permissions, menu registration, guarded routes, and the admin cleaner surface.
- Added snapshot-first residue inventory across tables, settings, permissions, navigation rows, storage paths, and package/module files.
- Added dry-run cleanup planning through the host `AddonModuleRegistry::purgeModuleResidue()` primitive.
- Added module-local audit logging in `module_cleaner_cleanup_logs`.
- Kept destructive cleanup out of the module: execution remains host-guarded by backup, typed confirmation, inactive-module checks, and step-up MFA.
- Added standalone contract and release ZIP tests.

Host minimum: `0.7.8t-CX`.
