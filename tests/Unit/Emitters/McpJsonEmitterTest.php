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
use stdClass;

function makeContext(InstalledPackages $packages, BoostConfig $config, ?string $projectRoot = null): SyncContext
{
    return new SyncContext(
        projectRoot: $projectRoot ?? '/tmp/test-project',
        packages: $packages,
        config: $config,
    );
}

/**
 * A real temp project root — the emitter now READS `.mcp.json` to merge into it,
 * so the fake path is only usable for the "no file on disk" cases.
 */
function makeMcpProjectRoot(?string $mcpJson = null): string
{
    $root = sys_get_temp_dir() . '/pkg-boost-mcp-' . bin2hex(random_bytes(8));
    mkdir($root, 0o755, recursive: true);

    if ($mcpJson !== null) {
        file_put_contents($root . '/.mcp.json', $mcpJson);
    }

    return $root;
}

function rmMcpProjectRoot(string $root): void
{
    if (is_file($root . '/.mcp.json')) {
        unlink($root . '/.mcp.json');
    }

    if (is_dir($root)) {
        rmdir($root);
    }
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

it('preserves other MCP servers already in .mcp.json instead of replacing the file', function (): void {
    // The clobber this emitter used to cause: it returned a whole document holding
    // only `laravel-boost`, and boost-core writes emitter output wholesale — so an
    // operator's other servers were deleted on every sync, silently once boost
    // owned the path.
    $root = makeMcpProjectRoot(json_encode([
        'mcpServers' => [
            'mcp-atlassian' => ['command' => 'uvx', 'args' => ['mcp-atlassian']],
            'laravel-boost' => ['command' => 'php', 'args' => ['artisan', 'boost:mcp']],
        ],
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

    try {
        $ctx = makeContext(makeBoostAndTestbenchPackages(), makeConfig([Agent::CLAUDE_CODE]), $root);

        $files = emitFiles($ctx);
        expect($files)->toHaveCount(1);

        $decoded = json_decode($files[0]->content, true, 512, JSON_THROW_ON_ERROR);

        expect($decoded)->toBe([
            'mcpServers' => [
                'mcp-atlassian' => ['command' => 'uvx', 'args' => ['mcp-atlassian']],
                'laravel-boost' => ['command' => 'vendor/bin/testbench', 'args' => ['boost:mcp']],
            ],
        ]);
    } finally {
        rmMcpProjectRoot($root);
    }
});

it('keeps unrelated top-level keys and extra keys on the laravel-boost entry', function (): void {
    $root = makeMcpProjectRoot(json_encode([
        'inputs' => [['id' => 'token', 'type' => 'promptString']],
        'mcpServers' => [
            'laravel-boost' => [
                'command' => 'php',
                'args' => ['artisan', 'boost:mcp'],
                'alwaysLoad' => true,
                'env' => ['FOO' => 'bar'],
            ],
        ],
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

    try {
        $ctx = makeContext(makeBoostAndTestbenchPackages(), makeConfig([Agent::CLAUDE_CODE]), $root);

        $decoded = json_decode(emitFiles($ctx)[0]->content, true, 512, JSON_THROW_ON_ERROR);

        expect($decoded)->toBe([
            'inputs' => [['id' => 'token', 'type' => 'promptString']],
            'mcpServers' => [
                'laravel-boost' => [
                    'command' => 'vendor/bin/testbench',
                    'args' => ['boost:mcp'],
                    'alwaysLoad' => true,
                    'env' => ['FOO' => 'bar'],
                ],
            ],
        ]);
    } finally {
        rmMcpProjectRoot($root);
    }
});

it('returns the file byte-for-byte when it already says what we want', function (): void {
    // Idempotence without reformatting: a two-space-indented file the operator
    // maintains must not be rewritten into our four-space output on every sync.
    $raw = "{\n  \"mcpServers\": {\n    \"laravel-boost\": {\n      \"command\": \"vendor/bin/testbench\",\n      \"args\": [\"boost:mcp\"]\n    }\n  }\n}\n";
    $root = makeMcpProjectRoot($raw);

    try {
        $ctx = makeContext(makeBoostAndTestbenchPackages(), makeConfig([Agent::CLAUDE_CODE]), $root);

        expect(emitFiles($ctx)[0]->content)->toBe($raw);
    } finally {
        rmMcpProjectRoot($root);
    }
});

it('emits the file verbatim when the existing .mcp.json cannot be parsed', function (): void {
    // Malformed JSON / JSON5 comments: preserve, never overwrite. Emitting the
    // current bytes (rather than nothing) also keeps boost-core's ownership of a
    // file it created — a skip would silently hand it back as "foreign".
    $root = makeMcpProjectRoot("// a comment JSON5 style\n{ \"mcpServers\": {} }\n");

    try {
        $ctx = makeContext(makeBoostAndTestbenchPackages(), makeConfig([Agent::CLAUDE_CODE]), $root);

        $files = emitFiles($ctx);

        expect($files)->toHaveCount(1)
            ->and($files[0]->content)->toBe((string) file_get_contents($root . '/.mcp.json'));
    } finally {
        rmMcpProjectRoot($root);
    }
});

it('emits the file verbatim when mcpServers is not a map', function (): void {
    $root = makeMcpProjectRoot(json_encode(['mcpServers' => ['not', 'a', 'map']], JSON_THROW_ON_ERROR));

    try {
        $ctx = makeContext(makeBoostAndTestbenchPackages(), makeConfig([Agent::CLAUDE_CODE]), $root);

        $files = emitFiles($ctx);

        expect($files)->toHaveCount(1)
            ->and($files[0]->content)->toBe((string) file_get_contents($root . '/.mcp.json'));
    } finally {
        rmMcpProjectRoot($root);
    }
});

it('preserves an unrelated empty object instead of turning it into an array', function (): void {
    // An associative decode cannot tell `{}` from `[]`, so re-encoding would rewrite
    // another server's `env: {}` as `env: []`.
    $root = makeMcpProjectRoot(<<<'JSON'
        {
            "mcpServers": {
                "other": { "command": "x", "env": {} },
                "laravel-boost": { "command": "php", "args": ["artisan", "boost:mcp"] }
            }
        }
        JSON);

    try {
        $ctx = makeContext(makeBoostAndTestbenchPackages(), makeConfig([Agent::CLAUDE_CODE]), $root);

        expect(emitFiles($ctx)[0]->content)->toContain('"env": {}')
            ->not->toContain('"env": []');
    } finally {
        rmMcpProjectRoot($root);
    }
});

it('writes a fresh document when no .mcp.json exists yet', function (): void {
    $root = makeMcpProjectRoot();

    try {
        $ctx = makeContext(makeBoostAndTestbenchPackages(), makeConfig([Agent::CLAUDE_CODE]), $root);

        $decoded = json_decode(emitFiles($ctx)[0]->content, true, 512, JSON_THROW_ON_ERROR);

        expect($decoded)->toBe([
            'mcpServers' => [
                'laravel-boost' => ['command' => 'vendor/bin/testbench', 'args' => ['boost:mcp']],
            ],
        ]);
    } finally {
        rmMcpProjectRoot($root);
    }
});

it('repairs a laravel-boost entry that is missing fields, without warnings', function (): void {
    // `"laravel-boost": {}` and single-field entries are valid JSON — reading the
    // absent property unguarded would raise a warning, which a strict error handler
    // turns into an emitter throw (a failed sync).
    foreach (['{}', '{"command": "php"}', '{"args": ["artisan", "boost:mcp"]}'] as $entry) {
        $root = makeMcpProjectRoot('{"mcpServers": {"laravel-boost": ' . $entry . '}}');

        try {
            $ctx = makeContext(makeBoostAndTestbenchPackages(), makeConfig([Agent::CLAUDE_CODE]), $root);

            $decoded = json_decode(emitFiles($ctx)[0]->content, false, 512, JSON_THROW_ON_ERROR);
            expect($decoded)->toBeInstanceOf(stdClass::class);

            $repaired = $decoded->mcpServers->{'laravel-boost'};

            // Key order follows whatever the entry already had — only the values matter.
            expect($repaired->command)->toBe('vendor/bin/testbench')
                ->and($repaired->args)->toBe(['boost:mcp'])
                ->and((array) $repaired)->toHaveCount(2);
        } finally {
            rmMcpProjectRoot($root);
        }
    }
});
