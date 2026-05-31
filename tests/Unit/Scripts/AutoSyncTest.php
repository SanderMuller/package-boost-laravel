<?php

declare(strict_types=1);

use Composer\Composer;
use Composer\IO\NullIO;
use Composer\Script\Event;
use SanderMuller\PackageBoostLaravel\Scripts\AutoSync;

/**
 * Spy {@see Event} that counts `isDevMode()` calls so a test can prove the
 * façade delegated into boost-core's BoostAutoSync (which consults
 * `isDevMode()` as its `--no-dev` guard). A non-dev event makes that guard
 * short-circuit before any binary resolution, so no real sync is spawned.
 */
final class AutoSyncSpyEvent extends Event
{
    public int $isDevModeCalls = 0;

    public function __construct(bool $devMode)
    {
        parent::__construct('post-install-cmd', new Composer(), new NullIO(), $devMode);
    }

    #[Override]
    public function isDevMode(): bool
    {
        ++$this->isDevModeCalls;

        return parent::isDevMode();
    }
}

it('delegates run() into BoostAutoSync so the --no-dev guard fires through the façade', function (): void {
    $event = new AutoSyncSpyEvent(devMode: false);

    AutoSync::run($event);

    // isDevMode was reached → the call delegated into BoostAutoSync; it
    // returned false → the --no-dev guard short-circuited before any sync.
    expect($event->isDevModeCalls)->toBe(1);
});

it('delegates runWithSummary() into BoostAutoSync so the --no-dev guard fires through the façade', function (): void {
    $event = new AutoSyncSpyEvent(devMode: false);

    AutoSync::runWithSummary($event);

    expect($event->isDevModeCalls)->toBe(1);
});
