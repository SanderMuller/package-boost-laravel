# Changelog

All notable changes to `sandermuller/package-boost-laravel` will be documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
