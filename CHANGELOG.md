# Changelog

All notable changes to `sandermuller/package-boost-laravel` will be documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/sandermuller/package-boost-laravel/compare/0.5.0...HEAD)

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
