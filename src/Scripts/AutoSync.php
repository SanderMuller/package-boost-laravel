<?php declare(strict_types=1);

namespace SanderMuller\PackageBoostLaravel\Scripts;

use Composer\Script\Event;
use SanderMuller\BoostCore\Scripts\BoostAutoSync;

/**
 * Thin Composer-script façade over boost-core's {@see BoostAutoSync}.
 *
 * Consumers wire their `post-install-cmd` / `post-update-cmd` (and any
 * `sync-ai`-style script) at this class instead of at boost-core's
 * `SanderMuller\BoostCore\Scripts\BoostAutoSync`. Two payoffs:
 *
 *  - **One-package install.** A consumer that requires only
 *    `sandermuller/package-boost-laravel` never has to name a
 *    `sandermuller/boost-core` symbol in its own `composer.json` —
 *    boost-core stays a transitive dependency, resolved through this
 *    package, never explicitly required.
 *  - **Insulation seam.** This façade is the stable reference point. If
 *    boost-core's callback surface ever shifts, the change is absorbed
 *    here once, not in every consumer's `composer.json`. boost-core's
 *    semver commitment keeps {@see BoostAutoSync::run()} /
 *    {@see BoostAutoSync::runWithSummary()} stable delegation targets.
 *
 * Every guard lives in boost-core and fires unchanged through this
 * delegate: skip on `BOOST_SKIP_AUTOSYNC`, skip on `--no-dev`, skip when
 * the `boost` binary is absent, and warn (not throw) on non-zero exit.
 *
 * @see BoostAutoSync for the full skip/error/exit semantics.
 *
 * @api The Composer-script entry point consumers wire into their
 * `post-install-cmd` / `post-update-cmd` (and any `sync-ai` script). The
 * `run()` / `runWithSummary()` static signatures are the frozen surface.
 */
final class AutoSync
{
    /**
     * Delegates to {@see BoostAutoSync::run()} — streams the sync summary
     * only when files changed; silent on a true no-op. Wire into
     * auto-firing `post-install-cmd` / `post-update-cmd` hooks.
     */
    public static function run(Event $event): void
    {
        BoostAutoSync::run($event);
    }

    /**
     * Delegates to {@see BoostAutoSync::runWithSummary()} — always streams
     * the success summary, including on a no-op. Wire into user-invoked
     * scripts (`composer sync-ai`) where silence reads as a no-op.
     */
    public static function runWithSummary(Event $event): void
    {
        BoostAutoSync::runWithSummary($event);
    }
}
