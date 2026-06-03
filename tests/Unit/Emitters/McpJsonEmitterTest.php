<?php

declare(strict_types=1);

// Namespaced under SanderMuller\* so PHPStan's `method.internal` same-root-namespace
// allowance applies: SyncContext has no public factory (the engine builds it; its
// constructor is @internal), so an emitter unit test must construct one directly.

namespace SanderMuller\PackageBoostLaravel\Tests\Unit\Emitters;

use SanderMuller\BoostCore\Config\BoostConfig;
use SanderMuller\BoostCore\Enums\Agent;
use SanderMuller\BoostCore\Sync\EmittedFile;
use SanderMuller\BoostCore\Sync\InstalledPackages;
use SanderMuller\BoostCore\Sync\PackageInfo;
use SanderMuller\BoostCore\Sync\SyncContext;
use SanderMuller\PackageBoostLaravel\Emitters\McpJsonEmitter;

function makeContext(InstalledPackages $packages, BoostConfig $config): SyncContext
{
    return new SyncContext(
        projectRoot: '/tmp/test-project',
        packages: $packages,
        config: $config,
    );
}

/**
 * @param  list<Agent>  $agents
 */
function makeConfig(array $agents): BoostConfig
{
    // Public construction path (the positional constructor is @internal — "build via configure()").
    return BoostConfig::configure()
        ->withAgents($agents)
        ->build('/tmp/test-project');
}

function makeBoostAndTestbenchPackages(): InstalledPackages
{
    return new InstalledPackages([
        'laravel/boost' => new PackageInfo('laravel/boost', '1.2.3', '/fake/vendor/laravel/boost'),
        'orchestra/testbench' => new PackageInfo('orchestra/testbench', '11.0.0', '/fake/vendor/orchestra/testbench'),
    ]);
}

/**
 * Drains the emitter's iterable return into a list of EmittedFile.
 *
 * @return list<EmittedFile>
 */
function emitFiles(SyncContext $ctx): array
{
    // array_values re-keys to a guaranteed list (emit() returns iterable, whose
    // spread is array<...> not list<...> to the analyzer).
    return array_values([...(new McpJsonEmitter())->emit($ctx)]);
}

it('emits .mcp.json when laravel/boost + testbench are installed and Claude Code is active', function (): void {
    $config = makeConfig([Agent::CLAUDE_CODE, Agent::CURSOR]);
    $ctx = makeContext(makeBoostAndTestbenchPackages(), $config);

    $files = emitFiles($ctx);

    expect($files)->toHaveCount(1)
        ->and($files[0]->relativePath)
        ->toBe('.mcp.json');

    $decoded = json_decode($files[0]->content, true);
    expect($decoded)->toBe([
        'mcpServers' => [
            'laravel-boost' => [
                'command' => 'vendor/bin/testbench',
                'args' => ['boost:mcp'],
            ],
        ],
    ]);
});

it('emits nothing when laravel/boost is NOT installed', function (): void {
    $packages = new InstalledPackages([
        'orchestra/testbench' => new PackageInfo('orchestra/testbench', '11.0.0', '/fake/vendor/orchestra/testbench'),
    ]);
    $config = makeConfig([Agent::CLAUDE_CODE]);
    $ctx = makeContext($packages, $config);

    expect(emitFiles($ctx))->toBeEmpty();
});

it('emits nothing when orchestra/testbench is NOT installed', function (): void {
    $packages = new InstalledPackages([
        'laravel/boost' => new PackageInfo('laravel/boost', '1.2.3', '/fake/vendor/laravel/boost'),
    ]);
    $config = makeConfig([Agent::CLAUDE_CODE]);
    $ctx = makeContext($packages, $config);

    expect(emitFiles($ctx))->toBeEmpty();
});

it('emits nothing when Claude Code is NOT in active agents', function (): void {
    $config = makeConfig([Agent::CURSOR, Agent::COPILOT]);
    $ctx = makeContext(makeBoostAndTestbenchPackages(), $config);

    expect(emitFiles($ctx))->toBeEmpty();
});

it('produces valid JSON', function (): void {
    $config = makeConfig([Agent::CLAUDE_CODE]);
    $ctx = makeContext(makeBoostAndTestbenchPackages(), $config);

    $files = emitFiles($ctx);
    expect($files)->toHaveCount(1);

    $decoded = json_decode($files[0]->content, true);
    expect($decoded)->toBeArray()
        ->toHaveKey('mcpServers');
});
