# Longlink Module Cleaner Release Notes

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
