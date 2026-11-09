<?php

namespace App\Console\Commands;

use App\Http\Middleware\EnsureMcpToken;
use App\Mcp\Tools\CreateTransactionTool;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Creates (or replaces) the token Claude Code uses for the Loger MCP server.
 *
 *   php artisan loger:mcp-token owner@example.com
 *   php artisan loger:mcp-token owner@example.com --read-only
 *   php artisan loger:mcp-token owner@example.com --revoke
 */
class McpTokenCreate extends Command
{
    public const TOKEN_NAME = 'Claude Code';

    protected $signature = 'loger:mcp-token {email : Owner of the token} {--read-only : Without permission to create transactions or mark payments} {--revoke : Only delete the existing token}';

    protected $description = 'Create the Sanctum token for the Loger MCP server (replaces any previous one).';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('No user with that email.');

            return self::FAILURE;
        }

        $revoked = $user->tokens()->where('name', self::TOKEN_NAME)->delete();
        if ($this->option('revoke')) {
            $this->info("Revoked {$revoked} token(s).");

            return self::SUCCESS;
        }

        $abilities = $this->option('read-only')
            ? [EnsureMcpToken::ABILITY]
            : [EnsureMcpToken::ABILITY, CreateTransactionTool::WRITE_ABILITY];
        $token = $user->createToken(self::TOKEN_NAME, $abilities)->plainTextToken;

        $this->info('Token created ('.implode(', ', $abilities).'). It is shown only once:');
        $this->line($token);
        $this->newLine();
        $this->line('claude mcp add --transport http loger '.url('/mcp').' --header "Authorization: Bearer '.$token.'"');

        return self::SUCCESS;
    }
}
