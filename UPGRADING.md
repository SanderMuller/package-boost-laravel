# Upgrading

## From 0.7.x to 0.8.0

`0.8.0` raises the `sandermuller/boost-core` floor to `^0.9.6` and the `sandermuller/package-boost-php` floor to `^0.10.2`. The 0.9.6 floor is load-bearing — it pins the path-ownership cleanup that removes the retired `.github/copilot-instructions.md` on `boost sync` (see step 4). Three behavioural changes consumers need to know about live upstream:

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

If your project pins `sandermuller/package-boost-php` or `sandermuller/boost-core` directly, bump those constraints too: `^0.10.2` and `^0.9.6` respectively.

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
```

After sync, `.gitignore`'s boost-managed block drops `AGENTS.md`, `CLAUDE.md`, `.github/copilot-instructions.md`, and `.github/skills/`. Add the two surviving files to git so operator-authored content outside the marker-bounded regions persists across machines.

Boost-core 0.9.6's path-ownership cleanup contract handles the retired Copilot file automatically: when `Agent::COPILOT` is in your active agents, `boost sync` deletes `.github/copilot-instructions.md` on disk. If you had it tracked from an earlier 0.8.x sync, the file is gone post-sync — `git status` shows the deletion, stage it. Copilot now reads root `AGENTS.md` per [GitHub Changelog 2025-08-28](https://github.blog/changelog/2025-08-28-copilot-coding-agent-now-supports-agents-md-custom-instructions/).

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
