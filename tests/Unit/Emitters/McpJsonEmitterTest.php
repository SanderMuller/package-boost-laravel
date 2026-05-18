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

function makeBoostAndTestbenchPackages(): InstalledPackages
{
    return new InstalledPackages([
        'laravel/boost' => new PackageInfo('laravel/boost', '1.2.3', '/fake/vendor/laravel/boost'),
        'orchestra/testbench' => new PackageInfo('orchestra/testbench', '11.0.0', '/fake/vendor/orchestra/testbench'),
    ]);
}

it('emits .mcp.json when laravel/boost + testbench are installed and Claude Code is active', function (): void {
    $config = makeConfig([Agent::CLAUDE_CODE, Agent::CURSOR]);
    $ctx = makeContext(makeBoostAndTestbenchPackages(), $config);

    $result = (new McpJsonEmitter())->emit($ctx);

    expect($result)->not->toBeNull()
        ->and($result->relativePath)
        ->toBe('.mcp.json');

    $decoded = json_decode($result->content, true);
    expect($decoded)->toBe([
        'mcpServers' => [
            'laravel-boost' => [
                'command' => 'vendor/bin/testbench',
                'args' => ['boost:mcp'],
            ],
        ],
    ]);
});

it('returns null when laravel/boost is NOT installed', function (): void {
    $packages = new InstalledPackages([
        'orchestra/testbench' => new PackageInfo('orchestra/testbench', '11.0.0', '/fake/vendor/orchestra/testbench'),
    ]);
    $config = makeConfig([Agent::CLAUDE_CODE]);
    $ctx = makeContext($packages, $config);

    expect((new McpJsonEmitter())->emit($ctx))->toBeNull();
});

it('returns null when orchestra/testbench is NOT installed', function (): void {
    $packages = new InstalledPackages([
        'laravel/boost' => new PackageInfo('laravel/boost', '1.2.3', '/fake/vendor/laravel/boost'),
    ]);
    $config = makeConfig([Agent::CLAUDE_CODE]);
    $ctx = makeContext($packages, $config);

    expect((new McpJsonEmitter())->emit($ctx))->toBeNull();
});

it('returns null when Claude Code is NOT in active agents', function (): void {
    $config = makeConfig([Agent::CURSOR, Agent::COPILOT]);
    $ctx = makeContext(makeBoostAndTestbenchPackages(), $config);

    expect((new McpJsonEmitter())->emit($ctx))->toBeNull();
});

it('produces valid JSON', function (): void {
    $config = makeConfig([Agent::CLAUDE_CODE]);
    $ctx = makeContext(makeBoostAndTestbenchPackages(), $config);

    $result = (new McpJsonEmitter())->emit($ctx);
    expect($result)->not->toBeNull();

    $decoded = json_decode($result->content, true);
    expect($decoded)->toBeArray()
        ->toHaveKey('mcpServers');
});
