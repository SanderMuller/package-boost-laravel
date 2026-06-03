# Upgrading

## From 0.13.x to 0.14.0

`0.14.0` adopts **boost-core 0.22**. It floor-bumps `sandermuller/package-boost-php` `^0.18.0` → `^0.18.1` (the release that widened its boost-core constraint to `^0.20||^0.21||^0.22`) and **re-introduces a direct `sandermuller/boost-core: ^0.22` require** in this package. The umbrella now resolves boost-core 0.22.0 + `sandermuller/boost-skills` 2.0.6.

### Why a direct `boost-core` require returned

0.11.0 dropped the direct boost-core require in favour of pure transitive resolution through package-boost-php. That no longer holds: boost-core 0.21 changed the `FileEmitter::emit()` contract to return `iterable`, and this package's `McpJsonEmitter` **hard-requires** that contract (boost-core ≥ 0.21). package-boost-php 0.18.1's range still *permits* boost-core 0.20, so the transitive range alone could resolve 0.20 and reverse-fatal the emitter. The umbrella therefore pins `boost-core: ^0.22` itself — an implementation floor, not a new thing consumers declare. You still require only `sandermuller/package-boost-laravel`.

### Action required

Bump the constraint and update the stack:

```diff
- "sandermuller/package-boost-laravel": "^0.13"
+ "sandermuller/package-boost-laravel": "^0.14"
```

```bash
composer update sandermuller/package-boost-laravel --with-all-dependencies
```

### If you author your own `FileEmitter`

boost-core 0.21 made `FileEmitter::emit()` return `iterable<EmittedFile>` (was `?EmittedFile`). A still-`?EmittedFile` implementation **hard-fatals** the moment boost-core loads it (`Declaration … must be compatible with FileEmitter::emit(): iterable`) — a raw PHP fatal at sync startup. Migrate your `emit()` **before** updating: change the return type to `iterable`, return `[]` instead of `null` to skip, and wrap a single emitted file in an array (`return [new EmittedFile(...)]`). See boost-core's [UPGRADING 0.20 → 0.21](https://github.com/sandermuller/boost-core/blob/main/UPGRADING.md).

### Nothing else changed

- `McpJsonEmitter` activation conditions, the `AutoSync` façade callback, and the `post-install-cmd` / `post-update-cmd` wiring — unchanged (the emitter's emit() signature changed, but it is `@internal` and engine-invoked, never called by consumers).
- `resources/boost/skills/` + `resources/boost/guidelines/laravel-packages.md` — unchanged.

## From 0.12.x to 0.13.0

`0.13.0` floor-bumps `sandermuller/package-boost-php` `^0.17.0` → `^0.18.0`. package-boost-php 0.18.0 narrows its boost-core constraint to `^0.20` (dropping 0.18/0.19), so the umbrella's transitively-inherited boost-core floor rises to `^0.20` — it now resolves boost-core 0.20.0 (and `sandermuller/boost-skills` 2.0.4, which widened to accept it). No code or API change in this package; `McpJsonEmitter` (on boost-core's now-locked `@api` `FileEmitter` contract) and the `AutoSync` façade are untouched.

### Breaking: `withTags()` is now array-typed

boost-core 0.20 changed every `BoostConfig` builder method — including `withTags()` — to take a single `array`. The previous **variadic** form fatals with a `TypeError` when your `boost.php` / `.config/boost.php` is loaded. Update it together with the constraint bump:

```diff
-    ->withTags(Tag::Php, Tag::Laravel, 'release-automation');
+    ->withTags([Tag::Php, Tag::Laravel, 'release-automation']);
```

`withAgents()`, `withAllowedVendors()`, `withExcludedSkills()`, and `withConventions()` already took arrays — only variadic `withTags()` callers need the edit. `boost sync` cannot auto-migrate this for you: loading the config executes the variadic call and throws before any rewrite can run.

### 1. Bump the constraint + fix `withTags()`

```diff
- "sandermuller/package-boost-laravel": "^0.12"
+ "sandermuller/package-boost-laravel": "^0.13"
```

```bash
composer update sandermuller/package-boost-laravel --with-all-dependencies
```

Then hand-edit the `withTags(...)` call in your `boost.php` / `.config/boost.php` to the array form above and run `vendor/bin/boost sync`.

### 2. If you pin the lower packages directly

You do not need to — the umbrella resolves the whole stack. If you pin `sandermuller/package-boost-php` directly, bump it to `^0.18.0`; boost-core then resolves to `^0.20`. A direct boost-core pin must allow `^0.20`.

### Nothing else changed

- `McpJsonEmitter` activation conditions, the `AutoSync` façade callback, and the `post-install-cmd` / `post-update-cmd` wiring — unchanged.
- `resources/boost/skills/` + `resources/boost/guidelines/laravel-packages.md` — unchanged.

## From 0.11.x to 0.12.0

`0.12.0` floor-bumps `sandermuller/package-boost-php` `^0.16.1` → `^0.17.0`. package-boost-php 0.17.0 **narrows its boost-core constraint** from `^0.16 || ^0.17 || ^0.18 || ^0.19` to `^0.18 || ^0.19`, dropping boost-core 0.16/0.17. Since the umbrella inherits boost-core transitively, the effective floor rises to boost-core `^0.18` — the umbrella now resolves boost-core 0.19.0 (and boost-skills 2.0.3, which widened to accept it).

This brings the **`.config/` config layout** (boost-core 0.17+): `boost.php` may live at `.config/boost.php`, with the sync manifest relocated from root `.boost/` to `.config/boost/` (boost-core 0.18+). This package now **dogfoods** that layout — its own config moved to `.config/boost.php`. No code or API change in this package; `McpJsonEmitter`, the `AutoSync` façade, and all shipped skills/guidelines are untouched.

### 1. Bump the constraint

```diff
- "sandermuller/package-boost-laravel": "^0.11"
+ "sandermuller/package-boost-laravel": "^0.12"
```

```bash
composer update sandermuller/package-boost-laravel --with-all-dependencies
```

### 2. If you pin the lower packages directly

You do not need to — the umbrella resolves the whole stack. But if your project pins `sandermuller/package-boost-php` directly, bump it to `^0.17.0`. boost-core then resolves to `^0.18 || ^0.19` transitively; if you keep a direct boost-core pin, it must allow `^0.18`.

### 3. Optional: adopt the `.config/` layout

boost-core resolves `boost.php` at the repo root **or** `.config/boost.php` — not both (having both is a hard error). Relocating is optional; a root `boost.php` keeps working unchanged. To adopt it: `git mv boost.php .config/boost.php` (avoid `__DIR__`-relative paths in the config — they break when the file moves), then `vendor/bin/boost sync` relocates the manifest to `.config/boost/` and rewrites the managed `.gitignore` block.

### Nothing else changed

- `McpJsonEmitter` activation conditions, the `AutoSync` façade callback, and the `post-install-cmd` / `post-update-cmd` wiring — unchanged.
- `resources/boost/skills/` + `resources/boost/guidelines/laravel-packages.md` — unchanged.

## From 0.10.x to 0.11.0

`0.11.0` floor-bumps `sandermuller/package-boost-php` `^0.15 || ^0.16` → `^0.16.1` and **drops the umbrella's direct `sandermuller/boost-core` require**. boost-core is now inherited purely transitively through `package-boost-php`, so the umbrella auto-tracks package-boost-php's boost-core range (`^0.13 || ^0.14 || ^0.15 || ^0.16` as of 0.16.1) instead of restating it — adopting a new boost-core line no longer needs an umbrella constraint bump, only a package-boost-php floor move when warranted.

This adds **boost-core 0.16 support**: package-boost-php 0.16.1 widened to accept `^0.16`, so the umbrella now resolves boost-core 0.16.0 (conventions-token leak detection — a no-op for packages that declare no conventions). No code or API change in this package; `McpJsonEmitter`, the `AutoSync` façade, and all shipped skills/guidelines are untouched.

### 1. Bump the constraint

```diff
- "sandermuller/package-boost-laravel": "^0.10"
+ "sandermuller/package-boost-laravel": "^0.11"
```

```bash
composer update sandermuller/package-boost-laravel --with-all-dependencies
```

### 2. If you pin the lower packages directly

You do not need to — the umbrella resolves the whole stack. But if your project pins `sandermuller/package-boost-php` directly, bump it to `^0.16.1`. You no longer need a direct `sandermuller/boost-core` pin at all; if you keep one, `^0.13 || ^0.14 || ^0.15 || ^0.16` matches package-boost-php's range.

### Nothing else changed

- `McpJsonEmitter` activation conditions, the `AutoSync` façade callback, and the `post-install-cmd` / `post-update-cmd` wiring — unchanged.
- `resources/boost/skills/` + `resources/boost/guidelines/laravel-packages.md` — unchanged.

## From 0.8.x to 0.9.0

`0.9.0` raises the `sandermuller/boost-core` floor `^0.10` → `^0.13` and the `sandermuller/package-boost-php` floor `^0.12` → `^0.15`. The two move as a joined pair: package-boost-php 0.15.0 requires boost-core `^0.13`, so adopting one pulls the other. This package authors no conventions and ships no wrapper, so the upstream changes are constraint-and-sync only — no code or API change here.

Three upstream behavioural changes land in this window:

1. **boost-core 0.12.0 — markerless agent-guidance files.** `CLAUDE.md` / `AGENTS.md` / `GEMINI.md` are now wholesale boost-owned and regenerated in full each sync, with no `<!-- boost-core:guidelines -->` markers. An empty-assembly guard never blanks a non-empty guidance file. On the first sync, marker comments are stripped and any content outside them is preserved once below the generated body. Put operator-authored guidance in `.ai/guidelines/`, not in the emission target.
2. **boost-core 0.13.0 — sync-ownership manifest.** A gitignored `.boost/manifest.json` records every emitted path with sha256 + category + provenance. boost-core's managed `.gitignore` block gains `.boost/` on the first 0.13 sync. No action beyond staging the `.gitignore` change.
3. **package-boost-php 0.13 → 0.15** widened then floor-bumped its boost-core constraint (`^0.10||^0.11` → `^0.12` → `^0.13`); the net effect for you is the `^0.15` floor above. No package-boost-php code or skill change.

### 1. Bump the constraint

```diff
- "sandermuller/package-boost-laravel": "^0.8"
+ "sandermuller/package-boost-laravel": "^0.9"
```

```bash
composer update sandermuller/package-boost-laravel --with-all-dependencies
```

If your project pins `sandermuller/package-boost-php` or `sandermuller/boost-core` directly, bump those to `^0.15` and `^0.13` respectively.

### 2. Re-sync and stage the managed-block change

```bash
vendor/bin/boost sync
git add AGENTS.md CLAUDE.md .gitignore
```

The first 0.12 sync strips guideline markers from `AGENTS.md` / `CLAUDE.md` (content preserved); the first 0.13 sync adds `.boost/` to the managed `.gitignore` block. Both are idempotent after the initial settle.

### 3. Opt into `boost-extension` only if you author a `FileEmitter`

If your package ships a custom `FileEmitter`, add `'boost-extension'` to `boost.php`'s `withTags([...])` to pull the `writing-file-emitter` + `skill-authoring` skills (gated off by default). Consumers that don't extend the engine skip this.

### Nothing else changed

- `McpJsonEmitter` activation conditions — unchanged.
- `resources/boost/skills/` + `resources/boost/guidelines/laravel-packages.md` — unchanged.
- Service provider registration + `BoostAutoSync::run` wiring — unchanged.

## From 0.7.x to 0.8.0

`0.8.0` raises the `sandermuller/boost-core` floor to `^0.10` and the `sandermuller/package-boost-php` floor to `^0.12`. The 0.10 line inherits 0.9.6's path-ownership cleanup that removes the retired `.github/copilot-instructions.md` on `boost sync` (see step 4) and adds the wrong-entry-point ergonomics cycle (doctor banner + three-case diagnostic split, gated on `project-boost-laravel` presence — does not fire for package-author projects). Three behavioural changes consumers need to know about live upstream:

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

If your project pins `sandermuller/package-boost-php` or `sandermuller/boost-core` directly, bump those constraints too: `^0.12` and `^0.10` respectively.

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
