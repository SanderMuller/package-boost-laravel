# package-boost-laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sandermuller/package-boost-laravel.svg?style=flat-square)](https://packagist.org/packages/sandermuller/package-boost-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/sandermuller/package-boost-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/sandermuller/package-boost-laravel/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/sandermuller/package-boost-laravel.svg?style=flat-square)](https://packagist.org/packages/sandermuller/package-boost-laravel)
[![License](https://img.shields.io/packagist/l/sandermuller/package-boost-laravel.svg?style=flat-square)](LICENSE)

> AI agent skills and guidelines for Laravel package authors. Adds three Laravel-flavored skills (`package-development` for Testbench, `cross-version-laravel-support` for `^12||^13` constraints, `ci-matrix-troubleshooting` for resolve/floor diagnostics) and the `laravel-packages` guideline (Testbench context, `vendor/bin/testbench` vs `php artisan`, cross-version Laravel support), plus `McpJsonEmitter` (writes `.mcp.json` when `laravel/boost` + `orchestra/testbench` are installed and Claude Code is an active agent). Inherits the framework-agnostic package-author skills and `foundation` guideline from [`package-boost-php`](https://github.com/sandermuller/package-boost-php).

## Install

```bash
composer require --dev sandermuller/package-boost-laravel
```

## Usage

```bash
vendor/bin/boost install   # interactive picker: agents + vendor allowlist (auto-generates boost.php)
vendor/bin/boost sync      # fan out skills + guidelines to selected agents
```

`McpJsonEmitter` runs automatically during `boost sync` when `laravel/boost` + `orchestra/testbench` are in your dep tree and `Agent::CLAUDE_CODE` is in your active agents.

Generated agent dirs are added to `.gitignore` automatically — edit `.ai/` only. Run `vendor/bin/boost sync` to regenerate them, or wire `SanderMuller\BoostCore\Scripts\BoostAutoSync::run` into your project's `post-install-cmd` / `post-update-cmd` (boost-core 0.6.0 retired its Composer plugin, so consumer auto-sync is opt-in now). Set `BOOST_SKIP_AUTOSYNC=1` to disable.

## License

MIT. See [LICENSE](LICENSE).
