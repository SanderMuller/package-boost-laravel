<?php declare(strict_types=1);

namespace SanderMuller\PackageBoostLaravel;

use Illuminate\Support\ServiceProvider;
use Override;

/**
 * The package's entry point into a host app — registered via
 * `extra.laravel.providers` for package discovery. Merges + publishes the
 * `package-boost-laravel` config (publish tag `package-boost-laravel-config`).
 *
 * @api Discovery contract: the class FQCN, the `package-boost-laravel` config
 * key, and the `package-boost-laravel-config` publish tag are the frozen
 * surface. `register()` / `boot()` are framework-invoked lifecycle hooks.
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
