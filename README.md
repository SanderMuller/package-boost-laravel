# package-boost-laravel

> AI agent skills for Laravel package authors. Adds three Laravel-flavored skills (`package-development` for Testbench, `cross-version-laravel-support` for `^11||^12||^13` constraints, `ci-matrix-troubleshooting` for resolve/floor diagnostics) plus `McpJsonEmitter` (writes `.mcp.json` when `laravel/boost` is installed and Claude Code is an active agent). Inherits five framework-agnostic package-author skills from [`package-boost-php`](https://github.com/sandermuller/package-boost-php).

## Install

Not yet on Packagist. While you wait, install via vcs repositories:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/sandermuller/package-boost-laravel" },
        { "type": "vcs", "url": "https://github.com/sandermuller/package-boost-php" }
    ],
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

```bash
composer require --dev sandermuller/package-boost-laravel
```

## Usage

```bash
composer boost:init      # generate boost.php starter (from boost-core)
composer boost:install   # interactive picker: agents + vendor allowlist
composer boost:sync      # fan out skills + guidelines to selected agents
```

`McpJsonEmitter` runs automatically during `boost:sync` when both `laravel/boost` is in your dep tree and `Agent::CLAUDE_CODE` is in your active agents.

## License

MIT. See [LICENSE](LICENSE).
