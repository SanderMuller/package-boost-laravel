<?php

declare(strict_types=1);

namespace SanderMuller\PackageBoostLaravel\Emitters;

use SanderMuller\BoostCore\Contracts\FileEmitter;
use SanderMuller\BoostCore\Enums\Agent;
use SanderMuller\BoostCore\Sync\EmittedFile;
use SanderMuller\BoostCore\Sync\SyncContext;
use stdClass;

/**
 * Emits `.mcp.json` for Laravel Boost integration with Claude Code.
 *
 * Conditional: only emits when `laravel/boost` + `orchestra/testbench` are
 * both installed AND `Agent::CLAUDE_CODE` is in the active agents list.
 * Testbench is required because the emitted command is
 * `vendor/bin/testbench boost:mcp` — without it the MCP server can't boot.
 * Yielding nothing (an empty iterable) skips the emission silently.
 *
 * **Merging, not replacing.** `.mcp.json` is a shared file: the operator adds
 * their own servers to it, and laravel/boost writes into it too (its own
 * `Install\Mcp\FileWriter` decodes the file and sets only its own key). boost-core
 * writes whatever an emitter returns wholesale, so returning a freshly-built
 * document here deleted every other `mcpServers` entry — silently, once boost
 * owned the path. This emitter therefore reads the current file and changes only
 * `mcpServers.laravel-boost`, leaving other servers, other top-level keys, and
 * unknown keys ON our own entry (`env`, `alwaysLoad`, …) intact.
 *
 * When the existing file cannot be parsed — malformed JSON, JSON5 comments, a
 * non-object shape — nothing is emitted rather than overwritten. Preserving an
 * unreadable file beats replacing it, and throwing is worse still: boost-core
 * counts an emitter throw as a sync error, which would fail the whole run over an
 * unrelated file.
 *
 * @internal This is package-boost-laravel's own emitter — discovered and
 * invoked by boost-core's sync engine, never called by consumers. Its
 * `emit()` signature tracks boost-core's {@see FileEmitter} contract (e.g.
 * the 0.21 `?EmittedFile` → `iterable` change), so it is not a stability
 * surface this package promises to downstream callers.
 */
final class McpJsonEmitter implements FileEmitter
{
    private const string FILE = '.mcp.json';

    private const string SERVERS_KEY = 'mcpServers';

    private const string SERVER_NAME = 'laravel-boost';

    private const string COMMAND = 'vendor/bin/testbench';

    /** @var list<string> */
    private const array ARGS = ['boost:mcp'];

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

        $path = rtrim($ctx->projectRoot, '/') . '/' . self::FILE;

        if (! is_file($path)) {
            return [$this->emitted($this->freshDocument())];
        }

        $raw = @file_get_contents($path);
        if ($raw === false) {
            return []; // unreadable — nothing to preserve or rewrite
        }

        $document = json_decode($raw);
        if (! $document instanceof stdClass) {
            return [$this->unchanged($raw)]; // malformed / JSON5 / not an object
        }

        $servers = $document->{self::SERVERS_KEY} ?? new stdClass();
        if (! $servers instanceof stdClass) {
            return [$this->unchanged($raw)]; // unknown shape we must not rewrite
        }

        $existing = $servers->{self::SERVER_NAME} ?? null;
        // Property reads are guarded: a valid `"laravel-boost": {}` or a
        // command-only entry is a repair case, not a reason to raise warnings (which
        // become exceptions under a strict error handler, and an emitter throw fails
        // the whole sync).
        if ($existing instanceof stdClass
            && ($existing->command ?? null) === self::COMMAND
            && ($existing->args ?? null) === self::ARGS
        ) {
            // Already says what we want: hand the ORIGINAL bytes back so the
            // operator's indentation and key order survive and the sync reports it
            // unchanged rather than reformatting the file on every run.
            return [$this->unchanged($raw)];
        }

        // Mutate the decoded OBJECT graph rather than an associative array: an
        // associative decode cannot tell `{}` from `[]`, so re-encoding would turn
        // an unrelated empty object (another server's `env: {}`) into `[]` and
        // renumber object keys that look numeric.
        $entry = $existing instanceof stdClass ? $existing : new stdClass();
        $entry->command = self::COMMAND;
        $entry->args = self::ARGS;

        $servers->{self::SERVER_NAME} = $entry;
        $document->{self::SERVERS_KEY} = $servers;

        return [$this->emitted($document)];
    }

    private function freshDocument(): stdClass
    {
        $entry = new stdClass();
        $entry->command = self::COMMAND;
        $entry->args = self::ARGS;

        $servers = new stdClass();
        $servers->{self::SERVER_NAME} = $entry;

        $document = new stdClass();
        $document->{self::SERVERS_KEY} = $servers;

        return $document;
    }

    /**
     * Emit the file's current bytes verbatim. Preferred over emitting nothing when
     * the file must be left alone: boost-core records an emitted path as owned, so a
     * skip would silently drop ownership of a `.mcp.json` boost created, and a later
     * sync would then treat it as a foreign file it merely took over.
     */
    private function unchanged(string $raw): EmittedFile
    {
        return new EmittedFile(relativePath: self::FILE, content: $raw);
    }

    private function emitted(stdClass $document): EmittedFile
    {
        return new EmittedFile(
            relativePath: self::FILE,
            content: json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n",
        );
    }
}
