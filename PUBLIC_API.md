# Public API

The semver-protected surface of `sandermuller/package-boost-laravel`. Everything under **Stable surface** is covered by [Semantic Versioning](https://semver.org/spec/v2.0.0.html): it will not break in a MINOR or PATCH of the same MAJOR. Everything marked `@internal`, plus all regenerable on-disk state, may change in any release.

This package is a thin Laravel umbrella. Most of what a consumer touches — the `boost.php` authoring API, the `vendor/bin/boost` CLI, the `FileEmitter` / `SkillRenderer` / `BoostWrapperContract` plugin contracts — belongs to [`sandermuller/boost-core`](https://github.com/sandermuller/boost-core/blob/main/PUBLIC_API.md) and is governed by *its* semver promise, inherited transitively. The framework-agnostic package-author toolkit (the `lean` / `gitattributes` CLI, the `foundation` guideline) belongs to `sandermuller/package-boost-php`. The surface below is only what *this* package adds on top.

## Versioning

This package follows Semantic Versioning 2.0.0. From `1.0.0` on, the surface below is locked for the `1.x` line — it will not break in a MINOR or PATCH. A consumer requires only `sandermuller/package-boost-laravel`; the rest of the stack resolves transitively.

## Stable surface

### Composer hooks

- `SanderMuller\PackageBoostLaravel\Scripts\AutoSync::run(Composer\Script\Event): void` — the `post-install-cmd` / `post-update-cmd` target. Streams the sync summary only when files changed; silent on a no-op.
- `SanderMuller\PackageBoostLaravel\Scripts\AutoSync::runWithSummary(Composer\Script\Event): void` — the user-invoked variant (`composer sync-ai`); always streams the summary, including on a no-op.

This façade is the insulation seam: consumers wire their composer scripts here, never at boost-core's `BoostAutoSync` directly, so a one-package install never names a boost-core symbol. Skip/error/exit semantics are boost-core's and fire unchanged through the delegate.

This package ships **no service provider**. It is a dev-time tool — its work happens during `boost sync` (the `.mcp.json` emitter below) and through the shipped skills/guidelines, none of which run inside a host application's container. There is no application-runtime config to merge or publish.

### `.mcp.json` emission (behavior contract)

During `boost sync`, this package emits `.mcp.json` pointing the MCP client at `vendor/bin/testbench boost:mcp` — but **only when** `laravel/boost` + `orchestra/testbench` are both installed AND `Agent::CLAUDE_CODE` is active. These activation conditions and the emitted `mcpServers.laravel-boost` shape are the contract; the emitter CLASS (`McpJsonEmitter`) that produces them is `@internal`.

### Shipped content

- The `laravel-packages` guideline and its detection rule (`require.illuminate/*` or `require.laravel/framework`).
- The Laravel-package skills shipped under `resources/boost/skills/` (their names are the discovery contract; whether a given skill ships still depends on the consumer's `withTags(...)`).

## Internal (not covered by semver)

- `SanderMuller\PackageBoostLaravel\Emitters\McpJsonEmitter` — `@internal`. Discovered + invoked by boost-core's sync engine via `extra.boost.emitters`, never called by consumers; its `emit()` signature tracks boost-core's `FileEmitter` contract.
- This repo's own `.config/boost.php` dogfood config — export-ignored, not shipped.
- All generated agent-directory output (`.claude/`, `.agents/`, `CLAUDE.md`, …) and the sync manifest under `.config/boost/` — regenerable, gitignored on consumer sides.

## Deprecation policy

A stable symbol slated for removal is marked `@deprecated` for at least one MINOR before removal in the next MAJOR, with the replacement named in the annotation and `UPGRADING.md`. New optional-with-default parameters on a stable method are additive, not breaking.
