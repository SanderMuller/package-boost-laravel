# Changelog

All notable changes to `sandermuller/package-boost-laravel` will be documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Initial scaffolding. Depends on `sandermuller/boost-core` + `sandermuller/package-boost-php` (currently via path repositories).
- 3 Laravel-flavored skills: `package-development`, `cross-version-laravel-support`, `ci-matrix-troubleshooting`.
- `McpJsonEmitter` (FileEmitter implementation) — emits `.mcp.json` when `laravel/boost` is detected and Claude Code is active.
- Inherits 5 framework-agnostic skills from `package-boost-php` via Composer dep resolution.

[Unreleased]: https://github.com/sandermuller/package-boost-laravel/compare/...HEAD
