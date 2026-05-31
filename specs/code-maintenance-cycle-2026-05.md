# Code Maintenance Cycle — Post-0.11.0 (2026-05)

## Overview

A periodic code-quality maintenance cycle for `package-boost-laravel`, run after a heavy release stretch (0.8.0 → 0.11.0: 29 commits across 6 releases — README rewrite, the `AutoSync` Composer-script façade, markerless-guidance + sync-manifest adoptions, and a long dependency-train of boost-core/package-boost-php constraint moves). The goal is to catch drift that no single feature task surfaces — stale shipped assets, test gaps, architectural inconsistency, doc rot — produce a prioritized backlog, and action the warranted items so the repo stays clean and ready for future work. This is the first instance of a recurring practice (see the `maintenance-cycle-cadence` project memory).

This spec is itself reviewed (incl. Codex) before implementation, per the established flow: spec → evaluate-incl-codex → implement.

---

## 1. Scope & current surface

The repo is small and focused — the cycle is proportionate (targeted skill passes, not a multi-agent sweep):

- `src/` — 3 classes, 128 lines:
  - `src/Emitters/McpJsonEmitter.php` (51) — `FileEmitter`; emits `.mcp.json`, gated on `laravel/boost` + `orchestra/testbench` + `Agent::CLAUDE_CODE` (returns `null` otherwise).
  - `src/Scripts/AutoSync.php` (53) — Composer-script façade; `run`/`runWithSummary` delegate to boost-core's `BoostAutoSync`.
  - `src/PackageBoostLaravelServiceProvider.php` (24).
- `tests/` — `McpJsonEmitterTest.php` (102), `AutoSyncTest.php` (50), `Pest.php`.
- `resources/boost/` — `guidelines/laravel-packages.md`, `skills/{package-development, cross-version-laravel-support, ci-matrix-troubleshooting}.md`.
- Tooling baseline: PHPStan `level: max` + `type_coverage` + `cognitive_complexity`; Pint; Rector; Pest 4 (+ arch + laravel plugins).

In scope: `src/`, `tests/`, `resources/boost/`, `README.md`, `UPGRADING.md`, `composer.json`, `boost.php`. Out of scope: the repo-init contract PR (separately owned, merged as repo-init 1.4.0); upstream boost-core/package-boost-php behavior.

## 2. Method

Each phase is **audit → record finding → conditional fix**. Audits are codebase research, not guesswork — read the actual files, diff against the last stable tag, run the tool. Findings (with verdicts) accumulate in `## Findings`. A fix is applied only when a finding is confirmed and warranted; trivial/deferrable items go to the backlog (Phase 5). Use the repo's own skills: `code-review`, `ai-guidelines`, `test-writing`, `simplify`, `evaluate`, `codex-review`, `pre-release`.

## Edge Cases

| Scenario | Handling |
|----------|----------|
| An audit phase finds nothing actionable | Record an explicit "no drift" finding for that phase and move on — a clean verdict is a valid result, not a gap to pad. |
| A doc/skill fix changes `resources/boost/` source | Re-run `vendor/bin/boost sync`; confirm idempotent (`wrote=0` on re-run) and no unintended tracked-file churn (Phase 2 Tests). |
| A test addition needs `Composer\Script\Event` or env fixtures | `putenv` is forbidden by the disallowed-calls ruleset — use the established `AutoSyncSpyEvent` subclass pattern (no `putenv`, no Mockery); do not relax the ruleset (Phase 4). |
| A fix would alter a `public` symbol (`McpJsonEmitter`, `AutoSync`, service provider) | Treat as a semver-relevant API change — defer to a deliberate release decision, do not fold silently into the maintenance pass (Phase 3). |
| A fix spans cross-version surface (illuminate ^12/^13, testbench ^10/^11, PHP ^8.3) | Verify against the support matrix; rely on CI `prefer-lowest` + matrix legs, not just local green (Phase 5 verify). |

## Implementation

### Phase 1: Recent-changes coherence review (Priority: HIGH)

- [ ] Review the cumulative diff `git diff 0.7.3..HEAD` across the 6 releases via the **code-review skill** — focus on coherence, not re-litigating shipped decisions. — catch half-migrations, leftover cruft, contradictory layering introduced across the façade / markerless / manifest / constraint-train work.
- [ ] Confirm `composer.json` is internally consistent post-0.11.0: require = `package-boost-php ^0.16.1` only; `boost-extension` + `release-automation` tags; `post-install`/`post-update` → `AutoSync::run`; `composer/composer` dev-dep present and used. — the require/scripts/tags surface changed a lot this session.
- [ ] Verify no orphaned references to removed/renamed things (e.g. lingering `BoostAutoSync::run` in this repo's own non-test code, stale `^0.x` constraint mentions in docs). — grep `src/`, `README.md`, `UPGRADING.md`, `boost.php`.
- [ ] **Dist-hygiene: export-ignore the new `specs/` dir** — `specs/` is now tracked but NOT in `.gitattributes`, so it would ship in the published Composer archive. Add `specs/ export-ignore` to the project-specific section (below the `# <<< package-boost (managed) <<<` block, alongside the existing `/CHANGELOG.md export-ignore` on line 27 — NOT inside the managed block, which `boost sync` regenerates). `internal/` needs nothing (gitignored, never in git). — confirmed during spec evaluation; this fix is in-scope for the cycle.
- [ ] Flag to repo-init (`dc8fud3l`) that the canonical laravel-package `.gitattributes` stub omits `specs/ export-ignore` while the write-spec convention defaults specs to `specs/` — likely a family-wide scaffold gap. — coordination note, not a code change here.
- [ ] Tests — confirm the full suite + PHPStan + Pint + Rector are green on `HEAD` as the review baseline; after the `.gitattributes` edit, `git check-ignore`/`composer archive --dry-run` (or inspect `git ls-files` vs export-ignore) confirms `specs/` is excluded from the dist.

### Phase 2: Shipped-assets freshness (Priority: HIGH)

Highest drift-risk: the 3 skills + 1 guideline ship to consumers and may describe pre-façade / pre-markerless wiring.

- [ ] Audit `resources/boost/skills/package-development.md` for stale scaffold/wiring guidance — does it describe `post-install-cmd`/`post-update-cmd` wiring, and if so does it name `BoostAutoSync::run` (stale) vs the `AutoSync` façade? Does it reference pre-markerless `CLAUDE.md` marker regions or pre-manifest `.boost/` behavior? — via **ai-guidelines skill**.
- [ ] Audit `resources/boost/skills/cross-version-laravel-support.md` + `ci-matrix-troubleshooting.md` for stale version ranges or constraint patterns that contradict the current support matrix. — these may cite specific Laravel/boost-core versions.
- [ ] Audit `resources/boost/guidelines/laravel-packages.md` for accuracy against current behavior (artisan-substitution table, Testbench commands, cross-version pointer). — the consumer-facing guideline.
- [ ] Apply warranted freshness fixes; delete stale content aggressively, do not pad. — per the readme/pre-release doc-audit discipline.
- [ ] Tests — after any `resources/boost/` edit, `vendor/bin/boost sync` then confirm idempotent re-sync (`wrote=0`) and clean tracked-file state.

### Phase 3: Architecture & public-API review (Priority: MEDIUM)

- [ ] Review the 3 `src/` classes for boundaries, `final`, and `@internal`/visibility markers — `McpJsonEmitter`, `AutoSync`, service provider are all public API; confirm `final` where extension isn't intended and that the public surface is the intended semver contract. — per the package's Public API Discipline.
- [ ] Decide + document the **boost-core-symbols-without-direct-require** posture — the umbrella uses `BoostAutoSync` (in `AutoSync`) and boost-core's `FileEmitter` contract (in `McpJsonEmitter`) but no longer directly requires boost-core (0.11.0 decision). Options: leave as-is (relies on package-boost-php's transitive provision), add a short `src` docblock noting the assumption, or a `composer.json` note. — Sander confirmed dropping the direct require; capture the rationale so it isn't re-litigated.
- [ ] Review `McpJsonEmitter` gate logic + `AutoSync` delegation for unnecessary complexity / duplication — a plain quality-cleanup review (the repo's `code-review` skill covers this; the built-in `simplify` skill may be used if present, but the spec does not depend on it). — small surface, but confirm it's clean.
- [ ] Tests — none (review/decision phase); any code change here triggers Phase 5 verification.

### Phase 4: Test-strategy audit (Priority: MEDIUM)

- [ ] Audit `McpJsonEmitterTest.php` coverage of all gate-fail paths — confirm explicit cases for: `laravel/boost` absent → `null`; `orchestra/testbench` absent → `null`; `Agent::CLAUDE_CODE` not in agents → `null`; all-present → emits `.mcp.json` with the expected command/args. Add any missing case. — the emitter's behavior is entirely its gate.
- [ ] Confirm `AutoSyncTest.php` delegation coverage is sufficient given the dropped `BOOST_SKIP_AUTOSYNC` case (putenv-forbidden) — the two `--no-dev` delegation proofs stand; decide whether a non-putenv skip-path proof is feasible or document why it's omitted. — keep the rationale on record.
- [ ] Consider a `pest-plugin-arch` rule asserting `Scripts/*` and `Emitters/*` invariants (e.g. `AutoSync` only delegates; emitter implements the contract) — add only if it earns its keep. — arch plugin is already a dev-dep.
- [ ] Tests — the additions themselves are the deliverable; run the full suite.

### Phase 5: Backlog consolidation, execute & verify (Priority: HIGH)

- [ ] Consolidate Phases 1-4 findings into a prioritized backlog in `## Findings` (High/Med/Low; action-now vs defer). — single source of truth for what shipped vs deferred.
- [ ] Run the **codex-review skill** on the applied changes (architecture + fixes) as an external second opinion; critically evaluate, apply warranted feedback. — matches the family review discipline.
- [ ] Run the **evaluate skill** loop until clean (Pint, PHPStan, full Pest suite). — completion gate.
- [ ] If any consumer-facing or shipped-asset change landed, run **pre-release** and hand off ready-to-tag (version bump per change shape — patch for doc/test-only, minor if public surface shifts). — Sander cuts the tag.
- [ ] Tests — full suite + PHPStan + Pint + Rector green; `boost sync` idempotent; CI green on the pushed commit.

---

## Open Questions

1. **Release shape for the cycle's output.** If Phases 2-4 only touch shipped skills/guideline + tests (no `src` public-API change), this is a patch (0.11.1). If Phase 3 alters a `public` symbol, it's a minor. Decide at Phase 5 once findings are known — likely patch.
2. **Arch-test adoption (Phase 4).** Whether a `pest-plugin-arch` invariant for `Scripts/`/`Emitters/` earns its keep on a 128-line `src`, or is over-engineering for the surface. Lean: add only if it expresses a real, non-obvious contract.

---

<!-- ## Resolved Questions
1. **{Original question?}** **Decision:** {What was decided.} **Rationale:** {Why.}
-->

## Findings

<!-- Notes added during implementation. Do not remove this section. -->
