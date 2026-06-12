# Longlink Module Cleaner Release Notes

## 0.1.0b-CX

- Populated the standalone `module_cleaner` addon module.
- Added the runtime provider, module-prefixed permissions, menu registration, guarded routes, and the admin cleaner surface.
- Added snapshot-first residue inventory across tables, settings, permissions, navigation rows, storage paths, and package/module files.
- Added dry-run cleanup planning through the host `AddonModuleRegistry::purgeModuleResidue()` primitive.
- Added module-local audit logging in `module_cleaner_cleanup_logs`.
- Kept destructive cleanup out of the module: execution remains host-guarded by backup, typed confirmation, inactive-module checks, and step-up MFA.
- Added standalone contract and release ZIP tests.

Host minimum: `0.7.8t-CX`.
