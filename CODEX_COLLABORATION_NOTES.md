# longlink-module-cleaner — collaboration context for Codex (CX)

You (Codex / CX) are sharing one cloned Git repository with Claude Code (CE).
We both push to the **same branch**, in alternating passes, and use handover
folders for chain-of-custody. This note re-establishes how we work so you
don't have to rediscover it from scratch each session.

## 1. Canonical Roots — Religious Rule (No Exceptions)

1. Every project has **ONE** canonical local clone root, named by the operator. For this project, it is the folder `longlink-module-cleaner/`, at the operator-named location alongside the other Longlink canonical roots. **Do not invent your own.**
2. Codex **MUST NOT** create duplicate clones, alternative root folders, "scratch" copies, "work" subdirectories, generic fallback folders, parallel worktrees, branching local repos, sibling test trees, or any other competing root **without explicit prior operator authorization** quoted in the relevant brief or commit message.
3. Codex **MUST NOT** create repositories — ever. Not forks, not mirrors, not "temporary" repos. Repo creation is an operator-only action.
4. If you find yourself wanting to create one — even for an apparently sensible reason — **STOP.** Ask the operator first.
5. The canonical root + canonical shared branch are the **only** surfaces you push to and pull from for this project.
6. If you accidentally create a competing root anyway: delete it immediately, report what happened in your next handover, and explain the prevention measure you've adopted.
7. This rule is religious. It overrides "convenience," "speed," "habit," and every reviewer's well-meaning suggestion. The operator has been burned by this before. Do not burn the operator again.

## 2. Repo + branch

- Repo: `tichm30/longlink-module-cleaner`
- Branch: `claude/funny-hawking-module-cleaner` (we BOTH push here; never a separate branch; `main` must never exist)
- Canonical project folder: `longlink-module-cleaner`
- Working dir: `module/` is the addon module itself (what gets zipped for release); `handovers/` is the shared release archive
- Always `git fetch && git merge --ff-only origin/...` BEFORE you start a pass — the other side may have pushed since your last session

## 3. Owner labels

- **CE** = Claude Code share. Commit prefix: `CE <ver>: ...`
- **CX** = Codex share. Commit prefix: `CX <ver>: ...`

## 4. Version + handover folder convention (same canon as the host repo)

- Patch versions advance through letters: 0.1.0a, 0.1.0b, ...
- Per release: `handovers/<version>-<owner>/` with these sidecars:

      <version>.zip
      <version>_CHECKSUMS.txt
      <version>_UPDATE_LOG.md
      <version>_HANDOVER_FOR_CLAUDE.md (CX's traditional name) or <version>_HANDOVER.md (CE)

- **CX delivery destination rule:** when Codex finishes a zip, the finished handover folder must exist in the canonical local clone at `handovers/<version>-CX/`, and the same commit must be pushed to `origin/claude/funny-hawking-module-cleaner`. Do not count a delivery as finished until both the GitHub remote and that local clone contain the zip. At the end of every delivery, run:

      git ls-remote origin refs/heads/claude/funny-hawking-module-cleaner

  Confirm the returned SHA contains the new handover folder, then paste the value into the handover-for-Claude file as:

      Delivery verified on remote: <sha>

- **Manageable batch delivery rule:** group related work into manageable, memory-safe batches; deliver a numbered zip after each completed batch; avoid waiting for one giant ultimate zip.

- **Three-zip live window with Drive archive:** keep only the latest three binary zips in the live handover tree; older binary zips move to the operator's Drive archive (NOT deleted). Non-binary sidecars stay in git for every release.

## 5. Build / release process

1. Make code changes inside `module/`.
2. Bump `module/module.json` → `version` to the new version string.
3. Update version-tracking docs in `docs/`.
4. Run `packaging/build-module-zip.sh` (produces `releases/longlink-module-cleaner-<ver>.zip` + SHA-256 sidecar).
5. Copy the zip + checksum into `handovers/<ver>-<owner>/`, write the UPDATE_LOG + HANDOVER.
6. `git add -A && git commit -m "CX <ver>: ..."` and `git push -u origin claude/funny-hawking-module-cleaner`.
7. Archive older binary zips to Drive per §4 three-zip rule.

## 6. Standing rules

- No keys / `.env` / live sessions / logs / uploads / nested zips / macOS artefacts in the release zip.
- The module manifest's declared `owned_tables` is authoritative for the disable/purge boundary. Keep it accurate; every table this module owns is prefixed `module_cleaner_`.
- Module migrations target only module-owned prefixed tables (per the host's addon-DB safety boundary, `assertAddonOwnedTable`).
- **Destructive operations go through host purge primitives only** (manifest-driven, forbidden-tables-guarded, super-admin-gated). The cleaner never issues raw `DROP TABLE`/`File::deleteDirectory` against surfaces it does not own; it orchestrates host-sanctioned removals. Host primitives land in the host repo FIRST; this module pins `requires_host_min_version` to them.
- **Safety order is fixed:** dry-run plan → (backup if enabled) → quarantine/move → confirmed destruction → audit log. Orphan candidates are always human-confirmed, never auto-dropped. Typed-confirmation gates on every destructive action, re-validated server-side.
- Worksuite-mined code: adapt, never paste wholesale; strip nwidart/Worksuite assumptions (Entities namespace, company context, modules_statuses.json, laraupdater, superadmin guard style); verify provenance/licence cleanliness for anything copied into a sellable module.
- Do not introduce host-side concerns into this repo (RBAC core seed, settings core schema, runtime broker, theming). Those live in the host portal.

## 7. Relationship to the host repo

- This repo: `tichm30/longlink-module-cleaner` — the module cleaner's development home.
- Host repo: `tichm30/longlink-licensing` — the host portal that loads addon modules through `ModuleRuntimeBroker` and owns the purge primitives the cleaner calls.
- This module is new-build (no host cutover needed): it never existed in the host tree. It ships as an installable addon from its first release.

## 8. Cross-repo coordination

When work needs a host-side runtime change (e.g. the residue/purge primitives, an uninstall-hook extension), land the host-side change first in `longlink-licensing` and tag it. Bump this module's `module.json` `requires_host_min_version` to that tag. Then ship the module change here.

## 9. First commit (initial seed)

The first commit on this repo's `claude/funny-hawking-module-cleaner` branch is the seed prepared by CE in the host repo at `handovers/seed-longlink-module-cleaner/`. Operator copies that seed in, makes the initial commit, pushes (`git branch -M claude/funny-hawking-module-cleaner` before the first push so `main` never exists), then pastes the remote SHA into the host repo's `CODEX_COLLABORATION_NOTES.md` §2b so both repos have a verifiable anchor to the start point. **Codex does not push here until that anchor is recorded.**
