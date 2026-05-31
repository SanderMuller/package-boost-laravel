---
name: package-development
description: Develop a Laravel package using Orchestra Testbench. Covers Testbench setup, service provider conventions, and the workbench dir layout.
---

# Laravel package development

## When to apply

- Bootstrapping a new Laravel-flavored Composer package
- Asked to "set up tests" or "add a workbench" for a Laravel package
- Reviewing a PR that adds a Service Provider or facade

## Testbench

Orchestra Testbench provides a headless Laravel kernel for testing packages
without a host application. Install as `require-dev`:

```bash
composer require --dev orchestra/testbench
```

Create `workbench/` for fixtures (migrations, routes, views, etc.). Layout:

```
workbench/
├── app/
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── resources/
└── routes/
```

The `testbench.yaml` at package root configures discovery:

```yaml
providers:
    - YourPackage\YourPackageServiceProvider

migrations:
    - workbench/database/migrations
```

## Service Provider

One per package, named `{PackageStudly}ServiceProvider`. Lives at
`src/{PackageStudly}ServiceProvider.php`. Registers via
`extra.laravel.providers` in composer.json:

```json
{
    "extra": {
        "laravel": {
            "providers": [
                "YourVendor\\YourPackage\\YourPackageServiceProvider"
            ]
        }
    }
}
```

## Anti-patterns

- Coupling tests to a real Laravel app (use Testbench instead)
- Magic discovery without an explicit Service Provider — every Laravel
  package should declare one
- Putting test fixtures in `tests/` instead of `workbench/` — Testbench
  conventions exist for a reason

## See also

- `cross-version-laravel-support` skill for `^12.0||^13.0` patterns
- `ci-matrix-troubleshooting` skill for resolve-stability test matrix
