# Laravel Package Guidelines

These guidelines supplement the framework-agnostic Package Boost
Guidelines (`foundation.md`) for Composer packages that target
Laravel. A consumer receives both files, composed — read this one
together with `foundation.md`, not instead of it.

Apply this file only when `composer.json` declares a Laravel
dependency — a `require.illuminate/*` entry or
`require.laravel/framework`. A framework-agnostic package ignores
everything below.

## Laravel Context

A Laravel package has no host application of its own. A Laravel
kernel is booted only at test time, by Orchestra Testbench. The base
test case is `Orchestra\Testbench\TestCase`.

- `composer.json`'s `require.illuminate/*` (or
  `require.laravel/framework`) defines the supported Laravel range.
  Check it before using a version-specific framework API.
- The service provider is the package's entry point into a host
  app. One per package, named `{PackageStudly}ServiceProvider`,
  registered under `extra.laravel.providers` for package discovery.
- Test fixtures — migrations, routes, views, factories — live under
  `workbench/`, not `tests/`. Testbench's conventions place them
  there; follow them.

## Use `vendor/bin/testbench`, not `php artisan`

Running artisan directly against the package fails — there is no
host application. Use Testbench's binary, which boots a kernel
first:

| Instead of | Use |
|---|---|
| `php artisan test` | `vendor/bin/pest` or `vendor/bin/phpunit` |
| `php artisan tinker` | `vendor/bin/testbench tinker` |
| `php artisan make:*` | Create files manually under `src/` |
| `php artisan vendor:publish` | `vendor/bin/testbench vendor:publish` |

Register the package's service provider in `testbench.yaml` under
`providers:` so Testbench boots it. Published files land in
`workbench/` by default, not the `config/` or `resources/` of a
host app.

### Commands that require `laravel/boost`

These apply only when the package has `laravel/boost` as a dev
dependency. Skip them if Boost is not installed — `boost sync`
prints a warning and moves on.

| Instead of | Use |
|---|---|
| `php artisan boost:install` | `vendor/bin/testbench boost:install` |
| `php artisan boost:mcp` | `vendor/bin/testbench boost:mcp` |

## Cross-Version Compatibility

Supporting multiple Laravel and PHP majors in one release is routine
for a Laravel package. Constraints use `||` between major ranges
(`^12.0||^13.0`), and CI runs a matrix that includes `prefer-lowest`
so the declared floor is actually exercised.

- Activate the `cross-version-laravel-support` skill **before**
  writing version-spanning code.
- Activate the `ci-matrix-troubleshooting` skill **after** a matrix
  cell has failed.
- See the `package-development` skill for the Testbench and
  `workbench/` layout.
