# Upgrading

## From 0.7.x to 0.8.0

`0.8.0` widens `sandermuller/boost-core` to `^0.8 || ^0.9` (via `package-boost-php` 0.10.1's matching widen) and adopts `package-boost-php` 0.10.x. Three behavioural changes consumers need to know about live upstream:

1. **boost-core 0.9.x** moves Project Conventions from CLAUDE.md's YAML body to `boost.php`'s `->withConventions([...])` chain, switches Copilot from `.github/copilot-instructions.md` to root `AGENTS.md`, and consolidates Copilot skills under `.agents/skills/`.
2. **package-boost-php 0.10.0** migrated the `readme`, `release-notes`, and `upgrading` skills out to `sandermuller/boost-skills` 1.6.0+ under the `release-automation` opt-in tag.
3. **boost-core 0.8.3 + 0.9.x** flipped the tracking model for `AGENTS.md`, `CLAUDE.md`, and (until retirement) `.github/copilot-instructions.md` — these are now tracked operator files with marker-bounded rendered regions, not gitignored generated output.

### 1. Bump the constraint

```diff
- "sandermuller/package-boost-laravel": "^0.7"
+ "sandermuller/package-boost-laravel": "^0.8"
```

```bash
composer update sandermuller/package-boost-laravel --with-dependencies
```

If your project pins `sandermuller/package-boost-php` directly, also widen to `^0.10.1` so the boost-core widening takes effect. If you pin `sandermuller/boost-core` directly, use `^0.8 || ^0.9` so `prefer-lowest` consumers keep resolving.

### 2. Allowlist `sandermuller/boost-skills` and opt into `release-automation`

The `readme`, `release-notes`, and `upgrading` skills are no longer published by `package-boost-php`. They ship from `sandermuller/boost-skills` under the `release-automation` tag. If your `boost.php` already adopted both, skip to step 3.

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

### 3. Migrate Project Conventions (only if you author them)

If your CLAUDE.md has a marker-bounded `## Project Conventions` YAML body authored by hand, migrate it once:

```bash
vendor/bin/boost convert-conventions --dry-run   # preview the boost.php rewrite
vendor/bin/boost convert-conventions             # apply
```

The command lifts the YAML values into `boost.php`'s `->withConventions([...])` chain. After it runs, `boost.php` becomes the source of truth; CLAUDE.md's marker-bounded region is regenerated from it on every sync. If you have no Project Conventions, skip this step — the default empty `withConventions` array applies and nothing changes.

### 4. Re-sync and track the new operator files

```bash
vendor/bin/boost sync
git add AGENTS.md CLAUDE.md .gitignore
git rm --cached .github/copilot-instructions.md   # only if it was tracked from a prior 0.8.x sync
```

After sync, `.gitignore`'s boost-managed block drops `AGENTS.md`, `CLAUDE.md`, `.github/copilot-instructions.md`, and `.github/skills/`. Add the two surviving files to git so operator-authored content outside the marker-bounded regions persists across machines.

If `.github/copilot-instructions.md` was tracked from an earlier 0.8.x sync, remove it — Copilot now reads root `AGENTS.md` per [GitHub Changelog 2025-08-28](https://github.blog/changelog/2025-08-28-copilot-coding-agent-now-supports-agents-md-custom-instructions/) and boost-core 0.9.x stops emitting it.

### Overlap-window collision

If your `boost.php` allowlist includes both `sandermuller/boost-skills >= 1.6.0` and a `sandermuller/package-boost-php < 0.10.0` pinned transitively somewhere, you will hit a vendor-vs-vendor skill collision (both vendors publishing `readme`, `release-notes`, `upgrading`). The 0.8.0 bump closes the window for this package's own consumers — if you still see it, audit your transitive `package-boost-php` version and bump.

The temporary workaround during the overlap is:

```php
->withExcludedSkills([
    'sandermuller/package-boost-php:readme',
    'sandermuller/package-boost-php:release-notes',
    'sandermuller/package-boost-php:upgrading',
])
```

Remove the exclusion once every `package-boost-php` in your resolved tree is `>= 0.10.0`.

### Nothing else changed

- `McpJsonEmitter` activation conditions — unchanged.
- `resources/boost/skills/` (`package-development`, `cross-version-laravel-support`, `ci-matrix-troubleshooting`) — unchanged.
- `resources/boost/guidelines/laravel-packages.md` — unchanged.
- Service provider registration — unchanged.
- `BoostAutoSync::run` wiring — unchanged.
