# package-boost-laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sandermuller/package-boost-laravel.svg?style=flat-square)](https://packagist.org/packages/sandermuller/package-boost-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/sandermuller/package-boost-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/sandermuller/package-boost-laravel/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/sandermuller/package-boost-laravel.svg?style=flat-square)](https://packagist.org/packages/sandermuller/package-boost-laravel)
[![License](https://img.shields.io/packagist/l/sandermuller/package-boost-laravel.svg?style=flat-square)](LICENSE)
[![Laravel Boost](https://badge.laravel.cloud/boost-badge.svg?style=flat-square)](https://github.com/laravel/boost)

AI agent skills, guidelines, and `.mcp.json` emission for Laravel-package authors. Inherits the framework-agnostic package-author toolkit from [`sandermuller/package-boost-php`](https://github.com/sandermuller/package-boost-php) and layers on Laravel-specific context: Testbench conventions, cross-version Laravel support, CI matrix diagnostics, and the `McpJsonEmitter` that wires `laravel/boost`'s MCP server into Claude Code during `boost sync`.

> Where [`laravel/boost`](https://github.com/laravel/boost) targets Laravel **application** developers, `package-boost-laravel` targets the people building Laravel **packages** — the dev-time codebase where `app/`, `bootstrap/`, and `.env` don't exist and `php artisan` doesn't apply. Coexists cleanly with `laravel/boost` if you also dogfood it from inside your package's test app.

## Which package fits your role?

| You're building                          | Install                                                                                       | Ships                                                                                                |
|------------------------------------------|-----------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------|
| A PHP application (not a package)        | [`sandermuller/project-boost`](https://github.com/sandermuller/project-boost)                 | App-dev skills: DDD layering, repository pattern, DI, domain modeling, legacy coexistence            |
| A Laravel application                    | [`sandermuller/project-boost-laravel`](https://github.com/sandermuller/project-boost-laravel) | `laravel/boost` MCP coexistence + nine-agent fanout + tag filter + remote skills                     |
| A framework-agnostic Composer package    | [`sandermuller/package-boost-php`](https://github.com/sandermuller/package-boost-php)         | Package-author skills + `lean` / `gitattributes` commands                                            |
| **A Laravel package**                    | **[`sandermuller/package-boost-laravel`](https://github.com/sandermuller/package-boost-laravel)** | **Laravel-package skills + `McpJsonEmitter`  ← you are here**                                    |
| Your own skill bundle, or custom tooling | [`sandermuller/boost-core`](https://github.com/sandermuller/boost-core)                       | The sync engine. You supply the skills.                                                              |

## What you get

**`McpJsonEmitter`** — the zero-overlap claim against `laravel/boost`. Writes `.mcp.json` on every `boost sync`, idempotent, with the command pointed at `vendor/bin/testbench boost:mcp` (not `php artisan`) so the MCP server actually boots in a package codebase. `laravel/boost` writes `.mcp.json` once at install time against `php artisan`, which doesn't exist here. The emitter fires only when all three conditions hold: `laravel/boost` is in your dev dependencies, `orchestra/testbench` is in your dev dependencies, and `Agent::CLAUDE_CODE` is in your active agents. Otherwise it returns null and skips silently.

**Three Laravel-flavored skills** — on-demand workflows for Laravel-package authorship. All three are untagged, so they ship whenever this package is installed.

| Skill                          | When it loads                                                                                                              | Tag |
|--------------------------------|----------------------------------------------------------------------------------------------------------------------------|-----|
| `package-development`          | Testbench conventions: `vendor/bin/testbench` vs `php artisan`, service-provider registration in `testbench.yaml`, `workbench/` layout for fixtures, migrations, routes, factories. | —   |
| `cross-version-laravel-support`| Supporting multiple Laravel majors in one release: `^12.0\|\|^13.0` constraint patterns, version-specific API shims, CI matrix shape including `prefer-lowest`. | —   |
| `ci-matrix-troubleshooting`    | Debugging "fails on prefer-lowest" / "fails on Laravel 13 but not 12" type matrix failures.                                | —   |

**One Laravel guideline** — pinned context for AI agents working in a Laravel-package codebase.

| Guideline          | Scope                                                                                                                                                                | Tag |
|--------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----|
| `laravel-packages` | Detection rule (`require.illuminate/*` or `require.laravel/framework`), Testbench context, artisan-substitution table, cross-version compatibility pointer. Composes with the framework-agnostic `foundation` guideline inherited from `package-boost-php`. | —   |

Inherits everything `package-boost-php` ships — `foundation` guideline, `lean` / `gitattributes` CLI commands, the `lean-dist` skill, and the release-flow content skills (`readme`, `release-notes`, `upgrading`) from `sandermuller/boost-skills` under the `release-automation` tag. The `skill-authoring` + `writing-file-emitter` skills ship too but are gated behind the `boost-extension` tag — declare it if your package extends the engine with a custom `FileEmitter` (this package does, for `McpJsonEmitter`).

## Install

```bash
composer require --dev sandermuller/package-boost-laravel
```

PHP 8.3+ and Laravel 12 or 13. `sandermuller/boost-core` and `sandermuller/package-boost-php` (and through the latter, `stolt/lean-package-validator`) come in transitively — do **not** require any of them separately, they resolve through this umbrella. A Laravel-package author requires only `sandermuller/package-boost-laravel` and gets the whole stack.

## First run

```bash
vendor/bin/boost install   # interactive: pick agents, allowlist vendors (writes boost.php)
vendor/bin/boost sync      # fan out skills + guidelines to selected agents
```

If `laravel/boost` + `orchestra/testbench` are in your dev deps and Claude Code is one of your active agents, `boost sync` writes `.mcp.json` automatically. Generated agent dirs (`.claude/`, `.cursor/`, etc.) are added to `.gitignore`; edit `.ai/` only, then re-run `vendor/bin/boost sync`.

## `boost.php` config

```php
<?php declare(strict_types=1);

use SanderMuller\BoostCore\Config\BoostConfig;
use SanderMuller\BoostCore\Enums\Agent;
use SanderMuller\BoostCore\Enums\Tag;

return BoostConfig::configure()
    ->withAgents([Agent::CLAUDE_CODE, Agent::COPILOT, Agent::CODEX])
    ->withAllowedVendors([
        'sandermuller/boost-skills',
        'sandermuller/package-boost-laravel',
        'sandermuller/package-boost-php',
    ])
    ->withTags(Tag::Php, Tag::Laravel, Tag::Github, 'release-automation');
```

The `release-automation` tag pulls the release-flow skills from `sandermuller/boost-skills`; add `'boost-extension'` to pull `skill-authoring` + `writing-file-emitter` if you author a custom `FileEmitter`. Full `BoostConfig` reference lives in [`sandermuller/boost-core`'s README](https://github.com/sandermuller/boost-core#readme).

## Coexistence and inheritance

Three relationships, distinct shapes:

- **Inherits from [`sandermuller/package-boost-php`](https://github.com/sandermuller/package-boost-php).** Required as a Composer dependency. Everything that package ships — the `foundation` guideline, the `lean` / `gitattributes` CLI, the framework-agnostic package-author skills — is available without re-declaring. This package layers Laravel-specific skills, the `laravel-packages` guideline, and `McpJsonEmitter` on top.
- **Coexists with [`laravel/boost`](https://github.com/laravel/boost) inside the package's test app.** Disjoint concerns: this package is dev-time package authorship (skills, guidelines, MCP wiring); `laravel/boost` is the MCP server + Laravel docs API your AI agent talks to once running. The `McpJsonEmitter` is the seam — it points the MCP client at `vendor/bin/testbench boost:mcp` so `laravel/boost`'s server can boot under Testbench. Without this emitter, `laravel/boost`'s install-time `.mcp.json` write targets `php artisan`, which doesn't exist in a package codebase.
- **Serves Laravel-package projects.** The consumer is a package author writing a Composer package that depends on Laravel. The guideline activates only when `composer.json` declares `require.illuminate/*` or `require.laravel/framework`; the skills are tagged for Laravel-package workflows.

## Auto-sync

To re-sync on every `composer install` / `composer update`, wire the callback into your project's `composer.json`:

```json
"scripts": {
    "post-install-cmd": ["SanderMuller\\BoostCore\\Scripts\\BoostAutoSync::run"],
    "post-update-cmd": ["SanderMuller\\BoostCore\\Scripts\\BoostAutoSync::run"]
}
```

`BOOST_SKIP_AUTOSYNC=1` disables the callback. (Boost-core 0.6.0 retired its Composer plugin; consumer auto-sync is opt-in via this script hook.)

## Testing

```bash
composer test
```

## License

MIT. See [LICENSE](LICENSE).
