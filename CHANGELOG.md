# Changelog

All notable changes to `sandermuller/package-boost-laravel` will be documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/sandermuller/package-boost-laravel/compare/0.3.0...HEAD)

### Added

- Initial scaffolding. Depends on `sandermuller/boost-core` + `sandermuller/package-boost-php` (currently via path repositories).
- 3 Laravel-flavored skills: `package-development`, `cross-version-laravel-support`, `ci-matrix-troubleshooting`.
- `McpJsonEmitter` (FileEmitter implementation) — emits `.mcp.json` when `laravel/boost` is detected and Claude Code is active.
- Inherits 5 framework-agnostic skills from `package-boost-php` via Composer dep resolution.

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
