<?php declare(strict_types=1);

namespace SanderMuller\PackageBoostLaravel;

use Illuminate\Support\ServiceProvider;
use Override;

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
