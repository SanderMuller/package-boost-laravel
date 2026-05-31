---
name: cross-version-laravel-support
description: Support multiple Laravel major versions in a single package release. Constraint patterns, testing matrix, and what NOT to do.
---

# Cross-version Laravel support

## When to apply

- Authoring a Laravel package's composer.json `require` for `illuminate/*`
- Asked to "support Laravel 12 and 13"
- Reviewing a PR that bumps or constrains the Laravel range

## Constraint pattern

For new packages defaulting to the current+next majors (the canonical
sander floor — PHP 8.3+, Laravel 12+13; PHP 8.2 and Laravel 11 are
dropped because `laravel/pao` floors at PHP 8.3):

```json
{
    "require": {
        "php": "^8.3",
        "illuminate/contracts": "^12.0||^13.0",
        "illuminate/support": "^12.0||^13.0"
    }
}
```

Use `||` (not `,`) between major version ranges — `,` is AND, `||` is OR.
The same `||` technique extends to a third major if you need a wider
span; the floor above is just the current canonical default.

## Minimal illuminate footprint

Don't `require: laravel/framework`. Pick the specific Illuminate
components you actually use:

- `illuminate/contracts` — almost always needed (interfaces)
- `illuminate/support` — for traits, helpers (Collection, Str, Arr)
- `illuminate/console` — only if you ship Artisan commands
- `illuminate/queue` — only if you use queue dispatching
- `illuminate/redis` — only if you connect to Redis directly
- `illuminate/database` — only if you use Eloquent / query builder

Smaller surface = fewer breaking changes across Laravel majors.

## Test matrix

GitHub Actions matrix for `^12||^13`:

```yaml
strategy:
  matrix:
    php: ['8.3', '8.4']
    laravel: ['12.*', '13.*']
    stability: ['prefer-lowest', 'prefer-stable']
```

No `exclude` is needed at the PHP 8.3 floor — both Laravel 12 and 13
run on 8.3+. Add an `exclude` only when a future major raises the PHP
floor above a row you still support.

`prefer-lowest` catches "works in dev, breaks for the user who pinned an
old version". `prefer-stable` catches the upgrade path.

## Anti-patterns

- `^12.0` instead of `^12.0||^13.0`: lock yourself into one major forever
- Hard `composer require laravel/framework`: pulls in the entire
  framework as a transitive dep
- Skipping `prefer-lowest` in CI: ships breakage to old-version users
- `dev-master` constraint in tests: flaky, non-reproducible

## See also

- `ci-matrix-troubleshooting` skill for resolve-stability debugging
- `package-development` skill for Testbench across versions
