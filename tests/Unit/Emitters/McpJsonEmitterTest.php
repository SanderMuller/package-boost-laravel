<?php

declare(strict_types=1);

use SanderMuller\BoostCore\Config\BoostConfig;
use SanderMuller\BoostCore\Enums\Agent;
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
    return new BoostConfig(
        agents: $agents,
        allowedVendors: [],
        skillsPath: '/tmp/.ai/skills',
        guidelinesPath: '/tmp/.ai/guidelines',
        disabledEmitters: [],
    );
}

it('emits .mcp.json when laravel/boost is installed and Claude Code is active', function (): void {
    $packages = new InstalledPackages([
        'laravel/boost' => new PackageInfo('laravel/boost', '1.2.3', '/fake/vendor/laravel/boost'),
    ]);
    $config = makeConfig([Agent::CLAUDE_CODE, Agent::CURSOR]);
    $ctx = makeContext($packages, $config);

    $result = (new McpJsonEmitter)->emit($ctx);

    expect($result)->not->toBeNull();
    expect($result->relativePath)->toBe('.mcp.json');
    expect($result->content)->toContain('"mcpServers"');
    expect($result->content)->toContain('"laravel-boost"');
    expect($result->content)->toContain('/fake/vendor/laravel/boost/bin/boost');
});

it('returns null when laravel/boost is NOT installed', function (): void {
    $packages = new InstalledPackages([]);
    $config = makeConfig([Agent::CLAUDE_CODE]);
    $ctx = makeContext($packages, $config);

    expect((new McpJsonEmitter)->emit($ctx))->toBeNull();
});

it('returns null when Claude Code is NOT in active agents', function (): void {
    $packages = new InstalledPackages([
        'laravel/boost' => new PackageInfo('laravel/boost', '1.2.3', '/fake/vendor/laravel/boost'),
    ]);
    $config = makeConfig([Agent::CURSOR, Agent::COPILOT]);
    $ctx = makeContext($packages, $config);

    expect((new McpJsonEmitter)->emit($ctx))->toBeNull();
});

it('produces valid JSON', function (): void {
    $packages = new InstalledPackages([
        'laravel/boost' => new PackageInfo('laravel/boost', '1.2.3', '/fake/vendor/laravel/boost'),
    ]);
    $config = makeConfig([Agent::CLAUDE_CODE]);
    $ctx = makeContext($packages, $config);

    $result = (new McpJsonEmitter)->emit($ctx);
    expect($result)->not->toBeNull();

    $decoded = json_decode($result->content, true);
    expect($decoded)->toBeArray();
    expect($decoded)->toHaveKey('mcpServers');
});
