<?php

declare(strict_types=1);

namespace SanderMuller\PackageBoostLaravel\Emitters;

use SanderMuller\BoostCore\Contracts\FileEmitter;
use SanderMuller\BoostCore\Enums\Agent;
use SanderMuller\BoostCore\Sync\EmittedFile;
use SanderMuller\BoostCore\Sync\SyncContext;

/**
 * Emits `.mcp.json` for Laravel Boost integration with Claude Code.
 *
 * Conditional: only emits when `laravel/boost` + `orchestra/testbench` are
 * both installed AND `Agent::CLAUDE_CODE` is in the active agents list.
 * Testbench is required because the emitted command is
 * `vendor/bin/testbench boost:mcp` — without it the MCP server can't boot.
 * Yielding nothing (an empty iterable) skips the emission silently.
 *
 * @internal This is package-boost-laravel's own emitter — discovered and
 * invoked by boost-core's sync engine, never called by consumers. Its
 * `emit()` signature tracks boost-core's {@see FileEmitter} contract (e.g.
 * the 0.21 `?EmittedFile` → `iterable` change), so it is not a stability
 * surface this package promises to downstream callers.
 */
final class McpJsonEmitter implements FileEmitter
{
    /**
     * @return iterable<EmittedFile>
     */
    public function emit(SyncContext $ctx): iterable
    {
        if (! $ctx->packages->has('laravel/boost')) {
            return [];
        }

        if (! $ctx->packages->has('orchestra/testbench')) {
            return [];
        }

        if (! in_array(Agent::CLAUDE_CODE, $ctx->config->agents, true)) {
            return [];
        }

        $config = [
            'mcpServers' => [
                'laravel-boost' => [
                    'command' => 'vendor/bin/testbench',
                    'args' => ['boost:mcp'],
                ],
            ],
        ];

        return [new EmittedFile(
            relativePath: '.mcp.json',
            content: json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n",
        )];
    }
}
