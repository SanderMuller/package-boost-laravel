# package-boost-laravel

> AI agent skills for Laravel package authors. Adds Laravel-flavored skills + `.mcp.json` emission on top of [`package-boost-php`](https://github.com/sandermuller/package-boost-php).

Depends on `sandermuller/package-boost-php` (inherited framework-agnostic skills) and `sandermuller/boost-core` (sync mechanism).

## What this adds

**3 skills** (in addition to the 5 from package-boost-php):
- `package-development` — Testbench harness workflows
- `cross-version-laravel-support` — multi-version Laravel/PHP support
- `ci-matrix-troubleshooting` — resolve/floor diagnostics

**1 FileEmitter:**
- `McpJsonEmitter` — emits `.mcp.json` when `laravel/boost` is installed and Claude Code is an active agent. Bridges this tooling family with Laravel Boost's MCP server.

## Status

**Under construction.** boost-core + package-boost-php are not yet on Packagist. This package currently resolves them via path repositories pointing at sibling dirs.

## Installation

Coming soon:

```bash
composer require --dev sandermuller/package-boost-laravel
composer boost:init
composer boost:install
composer boost:sync
```

## License

MIT. See [LICENSE](LICENSE).
