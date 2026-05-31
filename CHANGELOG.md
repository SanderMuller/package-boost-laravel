# Changelog

All notable changes to `sandermuller/package-boost-laravel` will be documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/sandermuller/package-boost-laravel/compare/0.10.1...HEAD)

## [0.10.1](https://github.com/sandermuller/package-boost-laravel/compare/0.10.0...0.10.1) - 2026-05-31

<!-- verified-sha: db8edf97f2c28a73b2648d71fba7ad0c33b486ea -->
Non-breaking widen: opens `sandermuller/package-boost-php` 0.16.0.

### What's changed

#### Changed

- Widened the `sandermuller/package-boost-php` constraint `^0.15` → `^0.15 || ^0.16`. 0.16.0 adds package-boost-php's own `AutoSync` Composer-script façade — additive, and unused by this umbrella (which ships its own `SanderMuller\PackageBoostLaravel\Scripts\AutoSync`). Its boost-core constraint (`^0.13 || ^0.14 || ^0.15`) is identical to the 0.15.x line, so the umbrella's transitive resolution is unchanged. The widen keeps the umbrella from capping package-boost-php below its latest, so a downstream that independently pulls package-boost-php 0.16 is not diamond-blocked through this package.

### Consumer impact

- **Additive, non-breaking.** No code or API change. `AutoSync`, `McpJsonEmitter`, the service provider, and all shipped skills/guidelines are untouched.
- Action required: none.

**Full Changelog:** https://github.com/SanderMuller/package-boost-laravel/compare/0.10.0...0.10.1

## [0.10.0](https://github.com/sandermuller/package-boost-laravel/compare/0.9.1...0.10.0) - 2026-05-31

<!-- verified-sha: 75b91bd341c67a1238fedc51201fa75f4ed9f6d8 -->
One-package install, and the boost-core line opens to `^0.15`. A Laravel-package author now requires only `sandermuller/package-boost-laravel` and never names a `sandermuller/boost-core` symbol in their own `composer.json` — boost-core stays a transitive dependency, resolved through this umbrella.

### What's changed

#### Added

- **`SanderMuller\PackageBoostLaravel\Scripts\AutoSync`** — a thin Composer-script façade over boost-core's `BoostAutoSync`. Two static delegates, `run()` and `runWithSummary()`, each total-delegating to the boost-core equivalent (no logic of its own), so every guard — skip on `--no-dev`, skip on `BOOST_SKIP_AUTOSYNC`, skip when the `boost` binary is absent, warn-not-throw on failure — fires unchanged through the delegate. Wire `post-install-cmd` / `post-update-cmd` at this class instead of boost-core's. Two payoffs: a consumer's `composer.json` carries no boost-core symbol (one-package install), and the façade is an insulation seam — a future boost-core callback shift is absorbed here once, not in every consumer (boost-core's semver commitment keeps `run` / `runWithSummary` stable delegation targets).

#### Changed

- README install guidance is now explicit one-package: `sandermuller/boost-core` and `sandermuller/package-boost-php` (and through the latter, `stolt/lean-package-validator`) come in transitively — do not require any of them separately.
- This package's own `post-install-cmd` / `post-update-cmd` now reference the `AutoSync` façade instead of `BoostCore\Scripts\BoostAutoSync` (dogfood).
- Widened the `sandermuller/boost-core` constraint `^0.13 || ^0.14` → `^0.13 || ^0.14 || ^0.15`, opening boost-core 0.15.0 (the conventions-inlining engine — a no-op here, since this package declares no conventions) without dropping the lower floors. `sandermuller/package-boost-php` stays `^0.15`; its 0.15.2 widen already accepts boost-core `^0.15`, so the umbrella's transitive resolution is clear.

#### Internal

- Added `composer/composer ^2.7` to `require-dev` so `Composer\Script\Event` resolves for static analysis and the façade test. Dev-only — not inherited by consumers.
- Added a test that proves the façade delegates: a non-dev `Event` reaches `isDevMode()` through the delegate and the `--no-dev` guard short-circuits before any sync is spawned.

### Consumer impact

- **Additive, non-breaking.** `AutoSync` is new public API; nothing was removed or renamed.
- `McpJsonEmitter` + service provider — untouched. Still gates on `laravel/boost` + `orchestra/testbench` + `Agent::CLAUDE_CODE`.
- `resources/boost/skills/` + `resources/boost/guidelines/laravel-packages.md` — untouched.
- Existing consumers keep working as-is. To adopt the one-package wiring, point your `post-install-cmd` / `post-update-cmd` at `SanderMuller\PackageBoostLaravel\Scripts\AutoSync::run`. New scaffolds will pick this up through a forthcoming repo-init template update (swapped atomically with a require-floor bump so a fresh scaffold never references a class its resolved version lacks).

**Full Changelog:** https://github.com/SanderMuller/package-boost-laravel/compare/0.9.1...0.10.0

## [0.9.1](https://github.com/sandermuller/package-boost-laravel/compare/0.9.0...0.9.1) - 2026-05-31

<!-- verified-sha: f1f7ba8a1c3f1b1d6b4b863433663a4648d27b29 -->
Non-breaking widen: opens `sandermuller/boost-core` 0.14.0 without dropping the 0.13 floor.

### What's changed

#### Changed

- Widened the `sandermuller/boost-core` constraint `^0.13` → `^0.13 || ^0.14`. Gentle absorption — `0.13` stays a valid floor for downstreams while `0.14` becomes available. `sandermuller/package-boost-php` stays `^0.15`; its own 0.15.1 widen (`boost-core ^0.13 || ^0.14`) means the umbrella's transitive resolution no longer caps boost-core below 0.14.

boost-core 0.14.0 brings, relevant to this package's `McpJsonEmitter`:

- **Emitter-dormancy reap.** When `laravel/boost` is dropped and `McpJsonEmitter` returns null (dormant), boost-core now reaps the previously-emitted `.mcp.json` instead of leaving stale config pointing at a missing `vendor/bin/testbench boost:mcp`. The reap is sha-gated — a hand-edited `.mcp.json` is preserved, not deleted — and ownership is recorded only for files boost created fresh or already owned (no take-over of pre-existing operator content). This closes a stale-file gap surfaced through real adoption feedback.
- Agent-deselection orphan reap and `.gitignore` directory/per-file dedup also land in 0.14.0; neither requires action here.

#### Internal

- No code or API change. `McpJsonEmitter`'s durable contract is unchanged: it returns null (never throws) when its gate is unmet, only ever writes its managed `.mcp.json` path, and a disabled or errored emitter leaves existing output untouched. Returning null on a dropped `laravel/boost` is precisely the dormancy signal that now drives the reap.

### Consumer impact

- `McpJsonEmitter` + service provider — untouched. Still gates on `laravel/boost` + `orchestra/testbench` + `Agent::CLAUDE_CODE`.
- `resources/boost/skills/` + `resources/boost/guidelines/laravel-packages.md` — untouched.
- Action required: none for a patch widen. To pull boost-core 0.14.0's reap behavior into your own tree, run `composer update sandermuller/boost-core` once on `^0.14`.

**Full Changelog:** https://github.com/SanderMuller/package-boost-laravel/compare/0.9.0...0.9.1

## [0.9.0](https://github.com/sandermuller/package-boost-laravel/compare/0.8.0...0.9.0) - 2026-05-31

<!-- verified-sha: 2b0d4d5e2c7f600dfe6e3614299172fdc8470315 -->
Adopts the `boost-core` 0.13 line and the `package-boost-php` 0.15 line — the markerless agent-guidance model and the sync-ownership manifest — and declares the `boost-extension` tag so this package pulls the emitter-authoring skill it actually needs.

### What's changed

#### Breaking

- Raised `sandermuller/boost-core` floor `^0.10` → `^0.13` and `sandermuller/package-boost-php` floor `^0.12` → `^0.15`. The two move as a joined pair: package-boost-php 0.15.0 requires boost-core `^0.13`, so adopting one pulls the other. The intermediate boost-core lines (0.11 wrapper-injection drift awareness, 0.12 markerless guidance) and package-boost-php lines (0.13 widen, 0.14 retention floor-bump) are all subsumed by the new floors. No code or API change in this package — `McpJsonEmitter` and the service provider are untouched.
- **Markerless agent-guidance files (boost-core 0.12.0).** `CLAUDE.md` / `AGENTS.md` are now wholesale boost-owned and regenerated in full each sync, with no `<!-- boost-core:guidelines -->` markers. An empty-assembly guard never blanks a non-empty guidance file. On the first sync the marker comments are stripped and any content outside them is preserved once below the generated body — put operator-authored guidance in `.ai/guidelines/`.
- **Sync-ownership manifest (boost-core 0.13.0).** A gitignored `.boost/manifest.json` now records every emitted path with sha256, category, and provenance. boost-core's managed `.gitignore` block gains `.boost/` on the first 0.13 sync; stage that change.

Pre-1.0 Composer semver collapses minor and patch into "potentially breaking" — floor narrowing for downstreams is treated as breaking here in spirit even though the version number is a minor bump.

#### Added

- Declared the `boost-extension` tag in `boost.php`. This package authors a `FileEmitter` (`McpJsonEmitter`), so it is the consumer that should opt into the tag — it pulls the `writing-file-emitter` and `skill-authoring` skills, which are gated off by default for consumers that don't extend the engine. A "Extending boost-core" guidance note now renders into the agent-guidance files describing the opt-in.
- Added the `UPGRADING.md` 0.8.x → 0.9.0 migration entry: the joined floor-bump, the markerless-guidance and manifest sync changes, and the `boost-extension` opt-in.

#### Internal

- Enriched `phpstan.neon.dist` with a baseline include, the `spaze/phpstan-disallowed-calls` rule sets, full type-coverage and type-perfect enforcement, and a cognitive-complexity ceiling — sourced from dogfood across the boost family.
- Corrected the README's inherited-skills list: `skill-authoring` and `writing-file-emitter` ship from `package-boost-php` but are `boost-extension`-gated, not pulled by default.

### Consumer impact

- `McpJsonEmitter` + service provider — untouched. The emitter still gates on `laravel/boost` + `orchestra/testbench` + `Agent::CLAUDE_CODE`, and its `.mcp.json` output is not tracked in the new manifest (FileEmitter output is uncategorized).
- `resources/boost/skills/` (`package-development`, `cross-version-laravel-support`, `ci-matrix-troubleshooting`) and `resources/boost/guidelines/laravel-packages.md` — untouched.
- Auto-syncs through `BoostAutoSync::run` — untouched.
- Action required when bumping past `0.8.x`: widen the `package-boost-laravel` constraint to `^0.9`, run `composer update --with-all-dependencies`, then `boost sync` and stage the regenerated `AGENTS.md` / `CLAUDE.md` and the `.gitignore` managed-block `.boost/` addition. See [UPGRADING.md](https://github.com/SanderMuller/package-boost-laravel/blob/main/UPGRADING.md) for the full migration story.

**Full Changelog:** https://github.com/SanderMuller/package-boost-laravel/compare/0.8.0...0.9.0

## [0.8.0](https://github.com/sandermuller/package-boost-laravel/compare/0.7.3...0.8.0) - 2026-05-29

<!-- verified-sha: bc971a85c5e4e0d26e3b310de0301db5cf5981ea -->
### What's changed

#### Breaking

- Raised `sandermuller/boost-core` floor `^0.7` → `^0.10`. The 0.10 line ships the wrong-entry-point ergonomics cycle (`boost doctor` entry-point mismatch banner + `boost tags` three-case diagnostic split, both gated on `project-boost-laravel` presence so the engine surface stays framework-agnostic). The 0.10 line inherits 0.9.6's path-ownership cleanup (retired emit paths like `.github/copilot-instructions.md` are deleted on `boost sync` when the corresponding agent is active) and 0.9.0's Project Conventions move (CLAUDE.md YAML body → `boost.php`'s `->withConventions([...])` chain). This package authors no conventions, so the default `[]` applies and no migration command is required.
- Raised `sandermuller/package-boost-php` floor `^0.9` → `^0.12`. The 0.10.0 release migrated the `readme`, `release-notes`, and `upgrading` skills out to `sandermuller/boost-skills` 1.6.0+ under the `release-automation` opt-in tag. Consumers whose `boost.php` already allowlists `sandermuller/boost-skills` and declares `'release-automation'` in `withTags(...)` see no skill loss; otherwise add both.
- `AGENTS.md` and `CLAUDE.md` are now tracked. boost-core 0.8.3 + 0.9.1 dropped them from the boost-managed `.gitignore` block. Both files have marker-bounded regions for rendered guideline content; operator-authored content outside the markers survives `boost sync` round-trips. Both stay `export-ignore`'d, so they do not bloat the Composer archive.

Pre-1.0 Composer semver collapses minor and patch into "potentially breaking" — floor narrowing for downstreams is treated as breaking here in spirit even though the version number is a minor bump.

#### Added

- Rewrote the README end-to-end against the sandermuller boost-family README strategy (29 → 110 lines, in the 80-120 toolkit-package target). Added the Laravel Boost compatibility badge, the canonical "Which package fits your role?" routing table, and a `McpJsonEmitter`-led "What you get" section. The three-bullet Coexistence-and-inheritance section frames the three relationships this package sits inside: inherits from `package-boost-php`, coexists with `laravel/boost` in the test app, serves Laravel-package projects.
- Added `UPGRADING.md` with the full 0.7.x → 0.8.0 migration path: constraint bumps, `boost-skills` allowlist + `release-automation` opt-in, optional `vendor/bin/boost convert-conventions` for consumers who author Project Conventions, and the re-sync + tracking-flip steps for `AGENTS.md` / `CLAUDE.md`.

#### Internal

- Retired `.github/copilot-instructions.md`. GitHub Copilot reads root `AGENTS.md` per the [GitHub Changelog 2025-08-28](https://github.blog/changelog/2025-08-28-copilot-coding-agent-now-supports-agents-md-custom-instructions/); boost-core 0.9.x stops emitting the Copilot-specific file.
- Retired `.github/skills/`. boost-core 0.9.1 routes Copilot skills to the shared `.agents/skills/` + root `AGENTS.md` surface per the GitHub Changelog 2025-12-18 update for agent skills.
- Dogfood `boost.php` hygiene: added `Tag::Laravel` and `Tag::Pest` alongside the existing `Tag::Php` and `Tag::Github` to source the Laravel- and Pest-tagged skills from `sandermuller/boost-skills`. Stripped the no-op `->withDisabledEmitters([])` chain.

### Consumer impact

- `McpJsonEmitter` + service provider — untouched. The emitter still gates on `laravel/boost` + `orchestra/testbench` + `Agent::CLAUDE_CODE`.
- `resources/boost/skills/` (`package-development`, `cross-version-laravel-support`, `ci-matrix-troubleshooting`) and `resources/boost/guidelines/laravel-packages.md` — untouched.
- Auto-syncs through `BoostAutoSync::run` — untouched.
- Action required when bumping past `0.7.3`: widen the `package-boost-laravel` constraint and let `boost sync` update the project's `.gitignore` boost-managed block. If the project authors Project Conventions, run `vendor/bin/boost convert-conventions` once to migrate the CLAUDE.md YAML body into `boost.php->withConventions([...])`. See [UPGRADING.md](https://github.com/SanderMuller/package-boost-laravel/blob/main/UPGRADING.md) for the full migration story.

**Full Changelog:** https://github.com/SanderMuller/package-boost-laravel/compare/0.7.3...0.8.0

## [0.7.3](https://github.com/sandermuller/package-boost-laravel/compare/0.7.2...0.7.3) - 2026-05-25

### Changed

- **`sandermuller/boost-core` constraint widened to `^0.7`** (was `^0.6`). Pulls in [boost-core 0.7.0](https://github.com/SanderMuller/boost-core/releases/tag/0.7.0) — additive release; the new `withRemoteSkills(...)`, `SkillRenderer` plugin contract (`@experimental`), and `SyncEngine::sync` injection params are opt-in. No migration required.
- **`sandermuller/package-boost-php` constraint widened to `^0.9`** (was `^0.7`). Pulls in [package-boost-php 0.9.0](https://github.com/SanderMuller/package-boost-php/releases/tag/0.9.0) — drops the Composer plugin. Consumers scripted on `composer package-boost-php:lean` / `composer package-boost-php:gitattributes` must switch to `vendor/bin/package-boost-php <cmd>`. See [package-boost-php's UPGRADING](https://github.com/SanderMuller/package-boost-php/blob/main/UPGRADING.md) for the full migration.
- **Dropped `sandermuller/package-boost-php: true` from `config.allow-plugins`** — the dependency is no longer a plugin. Composer no longer prompts `Do you trust ...` on install / update. Consumers can drop the entry from their own `composer.json` on cleanup; leaving it is harmless.

### Consumer impact

- **Auto-syncs through `BoostAutoSync::run`** keep working unchanged.
- **`McpJsonEmitter` + service provider** — untouched.
- **`resources/boost/skills/` + `resources/boost/guidelines/laravel-packages.md`** — untouched.
- **Action only required** if a CI config or developer script invoked `composer package-boost-php:*` directly — swap to `vendor/bin/package-boost-php <cmd>`.

**Full Changelog**: https://github.com/SanderMuller/package-boost-laravel/compare/0.7.2...0.7.3

## [0.7.2](https://github.com/sandermuller/package-boost-laravel/compare/0.7.1...0.7.2) - 2026-05-23

### Internal

- **Dropped the redundant `repositories: vcs` entry for `sandermuller/package-boost-php`.** That entry predated `package-boost-php`'s 0.3.0 Packagist listing. Since then, every `composer` resolve hit `github.com` for the vcs source before falling back to Packagist anyway. The roundtrip caused a CI flake during the 0.7.1 tag-ref re-fire (`Could not authenticate against github.com` on the `P8.4 / L12 / prefer-stable` leg). Removing tightens resolution to Packagist only and eliminates the auth-prone roundtrip.
- **Deleted two stale loose `.ai/skills/` files.** `boost-config-shape.md` (local 78 lines vs canonical 107) is owned by `sandermuller/boost-core`; `writing-file-emitter.md` (23 diff lines) is owned by `sandermuller/package-boost-php`. Both already source into `.claude/skills/` from their vendor packages via `boost sync` (the project allowlists both), so the loose copies were dead code that drifted independently.

### Consumer impact: none

- `resources/boost/skills/` and `resources/boost/guidelines/laravel-packages.md` — untouched.
- `McpJsonEmitter` + service provider — untouched.
- `composer.json` `require` / `require-dev` — untouched. Only `repositories` (dev-only metadata for composer's resolve path) shrunk.
- `.ai/` is `export-ignore`d, so the deleted files were never in the Composer archive.

**Full Changelog**: https://github.com/SanderMuller/package-boost-laravel/compare/0.7.1...0.7.2

## [0.7.1](https://github.com/sandermuller/package-boost-laravel/compare/0.7.0...0.7.1) - 2026-05-23

### Internal

- **Adopted `sandermuller/boost-skills ^1.1`** as a dev dep, matching sibling `package-boost-php`. The 11 generic dev-tooling skills (`ai-guidelines`, `autoresearch`, `backend-quality`, `bug-fixing`, `code-review`, `codex-review`, `evaluate`, `implement-spec`, `pr-review-feedback`, `pre-release`, `write-spec`) are now sourced from this canonical vendor package instead of stale local `.ai/skills/` copies that drifted independently.
- **Pruned 11 stale `.ai/skills/` dev-copy dirs.** Kept the bespoke entries: `profile-app/` (no boost-skills equivalent), and the loose `boost-config-shape.md` + `writing-file-emitter.md` files (those are stale copies of `package-boost-php`-owned skills — separate drift-reconciliation track, not part of this change).
- **Wired `boost-skills` into `boost.php`** — added `sandermuller/boost-skills` to `withAllowedVendors()` and declared `withTags('php', 'github')`. Without these, `boost sync` would silently filter `boost-skills` out (vendor allowlist) and tag-gated skills (`pre-release`, `backend-quality`, `autoresearch`, `pr-review-feedback`) would not ship even when the vendor was allowed.

**Full Changelog**: https://github.com/SanderMuller/package-boost-laravel/compare/0.7.0...0.7.1

## [0.7.0](https://github.com/sandermuller/package-boost-laravel/compare/0.6.0...0.7.0) - 2026-05-22

Adopts the `boost-core` 0.6 family — `boost-core` 0.6.0 retired its Composer plugin (now `type: library`), so this release migrates `package-boost-laravel` off the plugin onto explicit script wiring. Bumps both `sandermuller/*` constraints; pairs with `package-boost-php` 0.7.0, which carries the matching `boost-core ^0.6` requirement.

### Breaking — consumer auto-sync is now opt-in

Pre-0.7.0, installing `package-boost-laravel` (which pulled `boost-core` as a Composer plugin) auto-synced `.ai/` to `.claude/`, `.github/`, etc. on every `composer install`. `boost-core` 0.6.0 retired that plugin, so the auto-sync no longer happens automatically. If you want it to keep happening, wire the script callback into your project's `composer.json`:

```json
"scripts": {
    "post-install-cmd": ["SanderMuller\\BoostCore\\Scripts\\BoostAutoSync::run"],
    "post-update-cmd":  ["SanderMuller\\BoostCore\\Scripts\\BoostAutoSync::run"]
}









```
A dependency's own `post-install-cmd` does not fire in a consuming project — only the root package's scripts run — so this must live in *your* `composer.json`. Otherwise, run `vendor/bin/boost sync` yourself (e.g. in CI). `BOOST_SKIP_AUTOSYNC=1` still disables the callback.

See [`boost-core`'s 0.5 → 0.6 UPGRADING](https://github.com/sandermuller/boost-core/blob/main/UPGRADING.md#05--06) for the full migration story.

### Changed

- `sandermuller/boost-core`: `^0.5` → `^0.6`
- `sandermuller/package-boost-php`: `^0.6` → `^0.7`
- `config.allow-plugins`: drop `sandermuller/boost-core` — no longer a plugin in 0.6.0. `sandermuller/package-boost-php` stays — it remains `type: composer-plugin`.
- `post-install-cmd` / `post-update-cmd`: now call `BoostAutoSync::run` (was `::runWithSummary`). `::run` is silent on no-op installs and only emits the sync summary when files actually changed — the documented behaviour for auto-firing hooks. (`::runWithSummary` is for user-invoked scripts where silence reads as a no-op.)
- README updated: `composer boost:install` → `vendor/bin/boost install` (the plugin-era subcommand is gone with the plugin), and the consumer auto-sync claim is reworded to match the opt-in reality.
- Test setup: `McpJsonEmitterTest`'s `BoostConfig` constructor call adds the new required `commandsPath` parameter introduced in `boost-core` 0.6.0. `McpJsonEmitter`'s own public API is unchanged.
- `.gitignore` boost-managed block picked up `.claude/commands/` and `.github/prompts/` from `boost-core` 0.6.0's new commands-path sync.

### Upgrading

```bash
composer update sandermuller/package-boost-laravel --with-all-dependencies









```
`--with-all-dependencies` lets composer move `boost-core` and `package-boost-php` together. If you want to keep automatic sync on `composer install`, also add the `BoostAutoSync::run` callback to your project's `post-install-cmd` / `post-update-cmd` (see Breaking above).

**Full Changelog**: https://github.com/SanderMuller/package-boost-laravel/compare/0.6.0...0.7.0

## [0.6.0](https://github.com/sandermuller/package-boost-laravel/compare/0.5.0...0.6.0) - 2026-05-22

Adds a Laravel-package authoring guideline and pairs it with `package-boost-php`'s restored `foundation.md`. Tracks the `package-boost-php` 0.6.0 family release.

### Added

- **`resources/boost/guidelines/laravel-packages.md`** — a guideline for Laravel-package authors: the Testbench context, the service provider as a package's entry point, `vendor/bin/testbench` vs `php artisan`, the `laravel/boost` command table, `testbench.yaml` provider registration, and cross-version Laravel support. Auto-discovered by `boost-core`'s `VendorScanner` — `resources/boost/guidelines/` is the default path, so no `composer.json` change is needed. It **supplements** `package-boost-php`'s `foundation.md`; a consumer receives both files, composed. Named `laravel-packages.md`, not `foundation.md`, to avoid a vendor-vs-vendor guideline name collision with the `foundation.md` inherited transitively from `package-boost-php`.

### Changed

- `sandermuller/package-boost-php`: `^0.5` → `^0.6`
- `post-install-cmd` / `post-update-cmd` now call `BoostAutoSync::runWithSummary` — `composer install` / `update` print a summary of the synced files.
- Removed the `extra.branch-alias` block — releases resolve by published tag, not `dev-main` snapshots.

`package-boost-php` 0.6.0 ships the restored `foundation.md` guideline. A caret constraint on a `0.x` version stops at the minor, so `^0.5` would not resolve `0.6.0` — this bump makes `foundation.md` and `laravel-packages.md` install paired. `sandermuller/boost-core` stays at `^0.5`: `package-boost-php` 0.6.0 still requires `boost-core ^0.5`.

This release is non-breaking — the new guideline is purely additive and the constraint bump only widens the resolvable `package-boost-php` range.

**Full Changelog**: https://github.com/SanderMuller/package-boost-laravel/compare/0.5.0...0.6.0

## [0.5.0](https://github.com/sandermuller/package-boost-laravel/compare/0.4.0...0.5.0) - 2026-05-21

### What boost-core 0.5.0 brings

`boost-core` 0.5.0 adds **tag-based conditional skill filtering**: a skill declares tags in its `SKILL.md` frontmatter (`metadata.boost-tags`), a project declares the tags it wants in `boost.php` via `->withTags(...)`, and `boost:sync` ships a vendor skill only when the project declares every tag that skill carries. Untagged skills always ship. The release also adds `->withExcludedSkills([...])` for per-skill deny-listing, a `Tag` convenience enum, a `composer boost:doctor` tag report, and a format-preserving `boost.php` writer.

The upgrade is hands-off — tag filtering stays inert until a skill declares `metadata.boost-tags` *and* a project declares `->withTags()`. See the [`boost-core` 0.5.0 release](https://github.com/sandermuller/boost-core/releases/tag/0.5.0) for the full feature set.

`package-boost-laravel`'s three skills — `package-development`, `cross-version-laravel-support`, `ci-matrix-troubleshooting` — declare no tags, so they continue to ship unconditionally to every consumer. For this package the bump is purely transitive: nothing about what `package-boost-laravel` emits changes.

### Changed

- `sandermuller/boost-core`: `^0.4` → `^0.5`
- `sandermuller/package-boost-php`: `^0.4` → `^0.5`

Both constraints move together — `package-boost-php` 0.5.0 is the floor and itself requires `boost-core ^0.5`. `boost-core` stays a direct `require` here because `McpJsonEmitter` implements its `FileEmitter` contract (referencing `SanderMuller\BoostCore\Contracts\FileEmitter`, `Sync\EmittedFile`, `Sync\SyncContext`, `Enums\Agent` directly) and `post-install-cmd` / `post-update-cmd` reference `BoostCore\Scripts\BoostAutoSync::run`.

**Full Changelog**: https://github.com/SanderMuller/package-boost-laravel/compare/0.4.0...0.5.0

## [0.4.0](https://github.com/sandermuller/package-boost-laravel/compare/0.3.0...0.4.0) - 2026-05-20

Tracks the `boost-core` 0.4.0 family release. `package-boost-laravel`'s own surface — `McpJsonEmitter`, the service provider, the three shipped skills — is unchanged. This release bumps the two `sandermuller/*` dependency constraints so downstream installs pick up the new `boost-core` skill-path layout.

### Breaking — user-scope skill paths relocated

`boost-core` 0.4.0 changes where user-scope skills are synced:

```
~/.{agent}/skills/<basename>/  →  ~/.{agent}/skills/<vendor>__<package>/












```
The slug now carries the full Composer `vendor/package` name with the slash rewritten to `__` — a sequence the Composer name spec forbids, so the mapping is collision-free across vendors. A one-time auto-migration with an ownership check relocates existing user-scope skill directories on the next sync; no manual action required.

Project-scope paths (`.claude/skills/`, `.github/skills/`) are unaffected. `package-boost-laravel`'s own `McpJsonEmitter` is a project-scope emitter and is likewise unaffected — for this package the bump is purely transitive.

### Changed

- `sandermuller/boost-core`: `^0.3.2` → `^0.4`
- `sandermuller/package-boost-php`: `^0.3.0` → `^0.4`

Both constraints move together — `package-boost-php` 0.4.0 is the floor and itself requires `boost-core ^0.4`. `boost-core` stays a direct `require` here because `post-install-cmd` / `post-update-cmd` reference `BoostCore\Scripts\BoostAutoSync::run` and `allow-plugins` lists it.

### Upgrading

```bash
composer update sandermuller/package-boost-laravel












```
The `post-update-cmd` auto-sync hook runs the skill-path migration on the next install/update. Skip it with `BOOST_SKIP_AUTOSYNC=1` if you want to defer the move.

**Full Changelog**: https://github.com/SanderMuller/package-boost-laravel/compare/0.3.0...0.4.0

## [0.3.0](https://github.com/sandermuller/package-boost-laravel/compare/...0.3.0) - 2026-05-18

First tagged release of `sandermuller/package-boost-laravel` — AI agent skills for Laravel package authors, with `.mcp.json` emission on top of `package-boost-php`.

### What ships

- **`McpJsonEmitter`** — generates a portable `.mcp.json` (`vendor/bin/testbench boost:mcp`) when `laravel/boost` + `orchestra/testbench` are installed and `Agent::CLAUDE_CODE` is in the active agent list. Gated on testbench presence so the emitted command always points at a binary that actually exists.
  
- **Three Laravel-flavored skills** in `resources/boost/skills/`:
  
  - `package-development` — Testbench-based package authoring
  - `cross-version-laravel-support` — `^12||^13` matrix support
  - `ci-matrix-troubleshooting` — diagnosing resolve failures and floor mismatches
  
- **Inherits five framework-agnostic skills** from `package-boost-php` (`lean-dist`, `readme`, `release-notes`, `skill-authoring`, `upgrading`).
  
- **Repo-init `laravel-package` baseline** — canonical CI matrix split (phpstan / pint-check / rector-check / run-tests / update-changelog), testbench + workbench bootstrap, ServiceProvider + publishable config, larastan-aware static analysis, type-perfect / cognitive-complexity / type-coverage rules, rector with Laravel + Pest sets.
  

### Requirements

| | |
|---|---|
| PHP | `^8.3` |
| Laravel | `^12.0` or `^13.0` |
| `laravel/boost` (consumer-side, optional) | `^2.0` or `^3.0` for `McpJsonEmitter` activation |
| `boost-core` | `^0.3.2` (carries the cross-platform `BoostAutoSync::run` hook + `BOOST_SKIP_AUTOSYNC` env-var escape hatch) |
| `package-boost-php` | `^0.3.0` |

Laravel 11 is intentionally not supported — `laravel/pao` (an essential dev-output formatter in the canonical baseline) requires `laravel/framework ^12.0+`, so the floor is L12.

### Install

```bash
composer require --dev sandermuller/package-boost-laravel













```
`post-install-cmd` / `post-update-cmd` are wired to `BoostCore\Scripts\BoostAutoSync::run`. Skip with `BOOST_SKIP_AUTOSYNC=1` if you want to opt out of auto-sync on `composer install`.

### CI matrix

`run-tests.yml` covers five legs:

- `P8.3 / L12 / testbench 10 / prefer-lowest` (floor)
- `P8.3 / L13 / testbench 11 / prefer-stable`
- `P8.4 / L12 / testbench 10 / prefer-stable`
- `P8.4 / L13 / testbench 11 / prefer-stable`
- `P8.4 / L13 / testbench 11 / prefer-lowest` (ceiling)

All green on this release SHA.

### Notes

- `extra.boost.skills` / `extra.boost.guidelines` are not declared — boost-core's `VendorScanner` defaults at `resources/boost/skills` + `resources/boost/guidelines` are the canonical convention.
- `extra.laravel.providers` registers `SanderMuller\PackageBoostLaravel\PackageBoostLaravelServiceProvider` via Laravel package discovery.
- The `.ai/` authoring dir is `export-ignore`d in `.gitattributes` — dev-tooling skill sources stay out of the published Composer archive.

**Full Changelog**: https://github.com/SanderMuller/package-boost-laravel/commits/0.3.0
