## 0.1.0z-CX

- Classifies Module Cleaner confirmation module key controls as references for the host generated-key gate.

Current package version: `0.1.0z-CX`.

## 0.1.0y-CX

- Routes retention-day settings through the host measured-input primitive with explicit day disclosure.

The Longlink Module Cleaner addon module. Source of truth for the safe-removal tool that uninstalls addon modules **completely** — no orphan database tables, no orphan files, no orphan settings/permissions — with backups, quarantine, dependency checks, and a full audit trail.

## What this repo is

- **One addon module, in its own repo, with its own release cycle.**
- The shipped artifact from each release is an installable addon zip (built from `module/`) that the host portal uploads through its Addon Modules admin lifecycle (review → install → enable).
- The host portal owns the runtime (broker, lifecycle UI, RBAC, settings core) **and owns the destructive purge primitives** (manifest-driven residue removal). This repo owns the cleaner's orchestration, detection, safety UX, and audit surfaces.

## What this repo is NOT

- Not a Laravel application by itself. The `module/` tree expects a host portal that implements `App\Support\Modules\ModuleRuntimeContract` and friends.
- Not a fork of the host, and not a copy of the Worksuite ModuleCleaner — Worksuite code is mined for concepts and adaptable fragments per the governing brief, never pasted wholesale.
- Not a free-roaming table dropper. Destructive operations go through host-sanctioned, super-admin-gated purge primitives that honour the forbidden-tables list and the per-module ownership manifests. The cleaner orchestrates; it does not bypass.

## Canonical root rule (religious)

**The canonical local clone root folder of this repo is `longlink-module-cleaner/`, at the operator-named location alongside the other Longlink canonical roots.** Do not create competing roots, scratch folders, alternative clones, sibling test trees, or any other duplicate without explicit prior operator authorization in a brief or commit. See `CODEX_COLLABORATION_NOTES.md` §1 for the full rule.

## Branch model

Shared development branch: `claude/funny-hawking-module-cleaner` — the repo's default and ONLY branch. Both CE and Codex push to that one branch. Never a separate one. `main` must never exist on this repo.

## Directory layout

```
module/                     ← the module itself; this is what gets zipped for release
  module.json               ← manifest (key, name, version, runtime.provider, owned_tables)
  src/                      ← ModuleCleanerModuleProvider + Services + Controllers + Policies
  routes/                   ← module-owned web.php (+ api.php if needed)
  resources/views/          ← module-owned Blade views (dashboard, residue, orphans, quarantine, backups, logs)
  resources/lang/           ← module-owned translations
  database/migrations/      ← module-owned migrations (tables prefixed module_cleaner_* ONLY)
tests/                      ← module's own test suite
docs/                       ← module-specific docs (requirements brief)
packaging/                  ← build-module-zip.sh: produces the installable addon zip
handovers/                  ← release archive (same shape as the host repo's)
```

## Module scope (summary — full spec in `docs/MODULE_REQUIREMENTS_BRIEF.md`)

Residue inventory per module (tables, settings, permissions, navigation, storage, package files — including modules already uninstalled by the host's lifecycle, which retains their records); orphan-table detection (information_schema diff against core + ownership manifests, prefix heuristics, migration parsing); dry-run-first removal plans; backup sets (SQL dump + files zip + manifest) and restore; quarantine (move-don't-delete with retention); dependency graph blocking unsafe removals; typed-confirmation gates; cleanup audit log; self-destruct.

## Delivery canon (mirrors the host repo)

- Every release: handover folder + zip + SHA-256 sidecar + update log + remote-verification stamp.
- Three-zip live window with Drive archive.
- Manageable batches (not strict one-fix-per-zip).
- Manifest version must equal the release filename version.

## Relationship to the host portal

1. Develop here.
2. `packaging/build-module-zip.sh` produces `releases/longlink-module-cleaner-<version>.zip`.
3. Upload that zip through the host portal's Addon Modules admin UI.
4. Review → install → enable per the host's standard lifecycle.

The governing brief lives in the host repo at `handovers/MODULE_GENERATOR_AND_CLEANER_CANONICAL_OUTLINE_BRIEF_FOR_CODEX.md`. Once this repo is live, `docs/MODULE_REQUIREMENTS_BRIEF.md` here is the living spec.
