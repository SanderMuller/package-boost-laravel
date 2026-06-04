<?php declare(strict_types=1);

namespace SanderMuller\PackageBoostLaravel;

use Illuminate\Support\ServiceProvider;
use Override;

/**
 * The package's entry point into a host app — registered via
 * `extra.laravel.providers` for package discovery. Merges + publishes the
 * `package-boost-laravel` config (publish tag `package-boost-laravel-config`).
 *
 * @internal Framework plumbing: discovered by FQCN, instantiated + invoked by
 * Laravel, never constructed/extended/called by consumers; `register()` /
 * `boot()` implement Laravel's `ServiceProvider` contract, not ours. What's
 * frozen is the DISCOVERY/BEHAVIOR contract — the class FQCN, the
 * `package-boost-laravel` config key, and the `package-boost-laravel-config`
 * publish tag — documented as such in PUBLIC_API.md, not promised as a
 * class-API surface. (Same posture as the discovered-by-name McpJsonEmitter.)
 */
final class PackageBoostLaravelServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/package-boost-laravel.php', 'package-boost-laravel');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/package-boost-laravel.php' => config_path('package-boost-laravel.php'),
            ], 'package-boost-laravel-config');
        }
    }
}
