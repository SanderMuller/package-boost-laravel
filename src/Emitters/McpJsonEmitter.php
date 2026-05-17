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
 * Conditional: only emits when both `laravel/boost` is installed AND
 * `Agent::CLAUDE_CODE` is in the active agents list. Returning null
 * skips the emission silently.
 */
final class McpJsonEmitter implements FileEmitter
{
    public function emit(SyncContext $ctx): ?EmittedFile
    {
        if (! $ctx->packages->has('laravel/boost')) {
            return null;
        }

        if (! in_array(Agent::CLAUDE_CODE, $ctx->config->agents, true)) {
            return null;
        }

        $boostBin = $ctx->packages->path('laravel/boost').'/bin/boost';

        $config = [
            'mcpServers' => [
                'laravel-boost' => [
                    'command' => 'php',
                    'args' => [$boostBin, 'mcp:start'],
                ],
            ],
        ];

        return new EmittedFile(
            relativePath: '.mcp.json',
            content: json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n",
        );
    }
}
