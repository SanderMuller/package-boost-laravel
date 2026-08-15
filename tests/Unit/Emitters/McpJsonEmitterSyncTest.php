<?php

declare(strict_types=1);

// End-to-end through boost-core's real SyncEngine rather than the emitter alone.
// The clobber this guards against was an INTERACTION: the emitter returned a whole
// document and the engine writes emitter output wholesale, so unit-testing the
// returned content could never have caught it. Lives under tests/Unit because the
// package has a single test suite.

namespace SanderMuller\PackageBoostLaravel\Tests\Unit\Emitters;

use SanderMuller\BoostCore\Sync\InstalledPackages;
use SanderMuller\BoostCore\Sync\PackageInfo;
use SanderMuller\BoostCore\Sync\SyncEngine;
use SanderMuller\PackageBoostLaravel\Emitters\McpJsonEmitter;

/**
 * A project root wired the way the engine expects: a `boost.php` with Claude Code
 * active, and a fake installed package whose `composer.json` declares this
 * package's emitter (that declaration is how boost-core discovers emitters).
 */
function makeEmitterSyncProject(?string $mcpJson = null): string
{
    $root = sys_get_temp_dir() . '/pkg-boost-sync-' . bin2hex(random_bytes(8));
    mkdir($root . '/vendor/sandermuller/package-boost-laravel', 0o755, recursive: true);

    file_put_contents(
        $root . '/vendor/sandermuller/package-boost-laravel/composer.json',
        json_encode([
            'name' => 'sandermuller/package-boost-laravel',
            'extra' => ['boost' => ['emitters' => [McpJsonEmitter::class]]],
        ], JSON_THROW_ON_ERROR),
    );

    file_put_contents(
        $root . '/boost.php',
        "<?php declare(strict_types=1);\n\nuse SanderMuller\\BoostCore\\Config\\BoostConfig;\nuse SanderMuller\\BoostCore\\Enums\\Agent;\n\nreturn BoostConfig::configure()\n    ->withAgents([Agent::CLAUDE_CODE])\n    ->withAllowedVendors(['sandermuller/package-boost-laravel']);\n",
    );

    if ($mcpJson !== null) {
        file_put_contents($root . '/.mcp.json', $mcpJson);
    }

    return $root;
}

function emitterSyncPackages(string $root): InstalledPackages
{
    return new InstalledPackages([
        'sandermuller/package-boost-laravel' => new PackageInfo(
            'sandermuller/package-boost-laravel',
            '1.0.0',
            $root . '/vendor/sandermuller/package-boost-laravel',
        ),
        'laravel/boost' => new PackageInfo('laravel/boost', '2.4.0', $root . '/vendor/laravel/boost'),
        'orchestra/testbench' => new PackageInfo('orchestra/testbench', '11.0.0', $root . '/vendor/orchestra/testbench'),
    ]);
}

function rmTreeEmitterSync(string $path): void
{
    if (! is_dir($path)) {
        return;
    }

    $entries = scandir($path);
    if ($entries === false) {
        return;
    }

    foreach ($entries as $entry) {
        if ($entry === '.') {
            continue;
        }

        if ($entry === '..') {
            continue;
        }

        $full = $path . '/' . $entry;
        if (is_dir($full) && ! is_link($full)) {
            rmTreeEmitterSync($full);
        } else {
            unlink($full);
        }
    }

    rmdir($path);
}

it('a real sync keeps an operator MCP server that was added after boost created .mcp.json', function (): void {
    // The silent-loss path: boost creates the file (so its manifest owns it), the
    // operator adds a second server later, and the next sync used to delete it with
    // no diagnostic at all.
    $root = makeEmitterSyncProject();

    try {
        $packages = emitterSyncPackages($root);

        SyncEngine::default($packages)->sync($root);
        expect(file_exists($root . '/.mcp.json'))->toBeTrue()
            ->and(file_get_contents($root . '/.mcp.json'))
            ->toContain('laravel-boost');

        // The operator adds their own server to the file boost created.
        file_put_contents($root . '/.mcp.json', json_encode([
            'mcpServers' => [
                'laravel-boost' => ['command' => 'vendor/bin/testbench', 'args' => ['boost:mcp']],
                'mcp-atlassian' => ['command' => 'uvx', 'args' => ['mcp-atlassian']],
            ],
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n");

        SyncEngine::default($packages)->sync($root);

        $after = json_decode((string) file_get_contents($root . '/.mcp.json'), true, 512, JSON_THROW_ON_ERROR);

        expect($after)->toBe([
            'mcpServers' => [
                'laravel-boost' => ['command' => 'vendor/bin/testbench', 'args' => ['boost:mcp']],
                'mcp-atlassian' => ['command' => 'uvx', 'args' => ['mcp-atlassian']],
            ],
        ]);
    } finally {
        rmTreeEmitterSync($root);
    }
});

it('a real sync leaves an unparseable .mcp.json untouched', function (): void {
    $raw = "// JSON5 comment\n{ \"mcpServers\": { \"other\": { \"command\": \"x\" } } }\n";
    $root = makeEmitterSyncProject($raw);

    try {
        SyncEngine::default(emitterSyncPackages($root))->sync($root);

        expect(file_get_contents($root . '/.mcp.json'))->toBe($raw);
    } finally {
        rmTreeEmitterSync($root);
    }
});

it('a repeated sync reports the .mcp.json unchanged instead of rewriting it', function (): void {
    $root = makeEmitterSyncProject();

    try {
        $packages = emitterSyncPackages($root);
        SyncEngine::default($packages)->sync($root);

        $before = (string) file_get_contents($root . '/.mcp.json');
        SyncEngine::default($packages)->sync($root);

        expect(file_get_contents($root . '/.mcp.json'))->toBe($before);
    } finally {
        rmTreeEmitterSync($root);
    }
});

it('keeps ownership of a .mcp.json boost created that later became malformed', function (): void {
    // Emitting the current bytes (instead of skipping) matters here: a skip would drop
    // the path from the manifest, and the next sync would treat a file boost itself
    // created as a foreign one it merely took over.
    $root = makeEmitterSyncProject();

    try {
        $packages = emitterSyncPackages($root);
        SyncEngine::default($packages)->sync($root);

        $broken = "// hand-edited into invalid JSON\n{ \"mcpServers\": { \"laravel-boost\": { } } }\n";
        file_put_contents($root . '/.mcp.json', $broken);

        SyncEngine::default($packages)->sync($root);

        expect(file_get_contents($root . '/.mcp.json'))->toBe($broken)
            ->and((string) file_get_contents($root . '/.boost/manifest.json'))->toContain('.mcp.json');
    } finally {
        rmTreeEmitterSync($root);
    }
});
