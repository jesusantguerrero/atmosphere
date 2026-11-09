<?php

namespace App\Providers;

use App\Mcp\Concerns\InteractsWithTeam;
use Carbon\CarbonInterval;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Laravel\Passport\Passport;
use Laravel\Passport\Scope;

/**
 * OAuth for apps that connect to Loger on the user's behalf (Claude Code via MCP).
 */
class PassportServiceProvider extends ServiceProvider
{
    public const READ_SCOPE = 'mcp:read';

    public function boot(): void
    {
        Passport::tokensCan([
            self::READ_SCOPE => 'View your household finances',
            InteractsWithTeam::WRITE_ABILITY => 'Record transactions and mark payments as paid',
        ]);
        Passport::defaultScopes([self::READ_SCOPE]);
        Passport::tokensExpireIn(CarbonInterval::days(30));
        Passport::refreshTokensExpireIn(CarbonInterval::days(90));

        Passport::authorizationView(fn (array $parameters) => Inertia::render('Auth/OAuthAuthorize', [
            'client' => [
                'id' => $parameters['client']->getKey(),
                'name' => $parameters['client']->name,
            ],
            'scopes' => collect($parameters['scopes'])->map(fn (Scope $scope) => $scope->id)->values(),
            'authToken' => $parameters['authToken'],
            'state' => $parameters['request']->query('state'),
            'csrfToken' => csrf_token(),
            'approveUrl' => route('passport.authorizations.approve'),
            'denyUrl' => route('passport.authorizations.deny'),
        ]));
    }
}
