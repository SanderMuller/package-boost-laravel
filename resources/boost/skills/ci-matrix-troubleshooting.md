---
name: ci-matrix-troubleshooting
description: Debug "fails on prefer-lowest" or "fails on Laravel 13 but not 12" type CI matrix failures.
---

# CI matrix troubleshooting

## When to apply

- A specific cell of the CI matrix is failing (`php=8.2, laravel=11, stability=prefer-lowest`)
- Asked to "fix the resolve-lowest job"
- Asked to "find the floor of dep X"

## Diagnose the floor

For a `prefer-lowest` failure, identify which dep installed an older
version than you expected:

```bash
composer update --prefer-lowest --prefer-dist --with-all-dependencies --dry-run
```

Compare the resolved versions against the constraints in `composer.json`.
Typical culprits:

- A transitive dep that bumped its minimum requirement after you wrote
  the constraint
- An `illuminate/*` floor lower than your `php` floor allows

## Fix patterns

**Bump a floor.** If `phpunit/phpunit: ^9.0` resolves to 9.0.0 which
needs PHP 7.3 (and your CI is on PHP 8.2), prefer-lowest hits PHP edge
cases. Bump to `^9.5` or `^10.0`.

**Add a conflict.** When you know an old version is broken with your
code:

```json
{
    "conflict": {
        "some/package": "<2.5.3"
    }
}
```

Better than a higher floor when the floor was correct for most consumers.

**Exclude a cell.** When a combination genuinely can't work (e.g.
Laravel 13 requires PHP 8.3+, you support PHP 8.2 with Laravel 11/12):

```yaml
exclude:
  - php: '8.2'
    laravel: '13.*'
```

## Cell-specific failures

**"Works on `prefer-stable`, fails on `prefer-lowest`":** a transitive
dep's floor is too low. Bump the direct dep's floor.

**"Works on Laravel 11, fails on Laravel 12":** a Laravel API changed.
Check `illuminate/*` upgrade guides for the breaking change, add
version-conditional code or bump your floor.

**"Works on PHP 8.2, fails on 8.4":** likely a deprecation that became
an error. Run the failing PHP version locally with `error_reporting(-1)`.

## Anti-patterns

- Removing `prefer-lowest` from the matrix because it's flaky → ships
  breakage to users
- Pinning a single working set with `composer.lock` committed → no
  longer testing the range
- Wide `^X.0` constraints with no `prefer-lowest` job → you don't
  actually know what works

## See also

- `cross-version-laravel-support` for the matrix shape
- `package-development` for Testbench across versions
