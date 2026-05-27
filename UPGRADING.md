# Upgrading

## From 0.7.x to 0.8.0

`0.8.0` pulls in [`sandermuller/package-boost-php` 0.10.0](https://github.com/SanderMuller/package-boost-php/releases/tag/0.10.0) (and transitively [`sandermuller/boost-core` 0.8.0](https://github.com/SanderMuller/boost-core/releases/tag/0.8.0)). The behavioural change consumers need to know about lives upstream: three skills moved out of `package-boost-php` into `sandermuller/boost-skills` under the `release-automation` opt-in tag.

### 1. Bump the constraint

```diff
- "sandermuller/package-boost-laravel": "^0.7"
+ "sandermuller/package-boost-laravel": "^0.8"
```

```bash
composer update sandermuller/package-boost-laravel --with-dependencies
```

If your project pins `sandermuller/package-boost-php` directly, also widen that to `^0.10`.

### 2. Allowlist `sandermuller/boost-skills` and opt into `release-automation`

The `readme`, `release-notes`, and `upgrading` skills are no longer published by `package-boost-php`. They now ship from `sandermuller/boost-skills` under the `release-automation` tag. If your `boost.php` already adopted both, you are set — skip to step 3.

Otherwise:

```diff
  return BoostConfig::configure()
      ->withAgents([Agent::CLAUDE_CODE])
      ->withAllowedVendors([
          'sandermuller/package-boost-laravel',
          'sandermuller/package-boost-php',
+         'sandermuller/boost-skills',
      ])
-     ->withTags(Tag::Php, Tag::Laravel);
+     ->withTags(Tag::Php, Tag::Laravel, 'release-automation');
```

Without both edits, the three release-flow skills silently disappear from your synced agent dirs after the bump.

### 3. Re-sync

```bash
vendor/bin/boost sync
```

Confirm the three skills land back under `.claude/skills/` (or whichever agent dirs you fan out to).

### Overlap-window collision

If your `boost.php` allowlist includes both `sandermuller/boost-skills >= 1.6.0` and a `sandermuller/package-boost-php < 0.10.0` pinned transitively somewhere, you will hit a vendor-vs-vendor skill collision (both vendors publishing `readme`, `release-notes`, `upgrading`). The 0.8.0 bump closes the window for this package's own consumers — if you see it, audit your transitive `package-boost-php` version and bump.

Per `package-boost-php`'s UPGRADING.md, the temporary workaround during the overlap is:

```php
->withExcludedSkills(['sandermuller/package-boost-php:readme', 'sandermuller/package-boost-php:release-notes', 'sandermuller/package-boost-php:upgrading'])
```

Remove the exclusion once every `package-boost-php` in your resolved tree is `>= 0.10.0`.

### Nothing else changed

- `McpJsonEmitter` activation conditions — unchanged.
- `resources/boost/skills/` (`package-development`, `cross-version-laravel-support`, `ci-matrix-troubleshooting`) — unchanged.
- `resources/boost/guidelines/laravel-packages.md` — unchanged.
- Service provider registration — unchanged.
- `BoostAutoSync::run` wiring — unchanged.
