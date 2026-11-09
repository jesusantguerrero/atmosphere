<?php

namespace Tests\Feature\Mcp;

use App\Models\User;
use App\Providers\PassportServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Token;
use Tests\TestCase;

class McpOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_discovery_documents_advertise_read_and_write_scopes(): void
    {
        $this->getJson('/.well-known/oauth-protected-resource/mcp')
            ->assertOk()
            ->assertJsonPath('resource', url('/mcp'))
            ->assertJsonPath('authorization_servers', [url('/')])
            ->assertJsonPath('scopes_supported', [PassportServiceProvider::READ_SCOPE, PassportServiceProvider::WRITE_SCOPE]);

        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonPath('authorization_endpoint', route('passport.authorizations.authorize'))
            ->assertJsonPath('token_endpoint', route('passport.token'))
            ->assertJsonPath('registration_endpoint', route('mcp.oauth.register'))
            ->assertJsonPath('code_challenge_methods_supported', ['S256'])
            ->assertJsonPath('scopes_supported', [PassportServiceProvider::READ_SCOPE, PassportServiceProvider::WRITE_SCOPE]);
    }

    public function test_unauthenticated_mcp_requests_point_to_the_resource_metadata(): void
    {
        $header = $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
            ->assertUnauthorized()
            ->headers->get('WWW-Authenticate');

        $this->assertStringContainsString('/.well-known/oauth-protected-resource', (string) $header);
    }

    public function test_claude_code_can_register_itself_as_a_public_client(): void
    {
        $this->postJson('/oauth/register', [
            'client_name' => 'Claude Code',
            'redirect_uris' => ['http://localhost:33418/callback'],
        ])
            ->assertCreated()
            ->assertJsonPath('scope', 'mcp:read mcp:write')
            ->assertJsonPath('token_endpoint_auth_method', 'none')
            ->assertJsonStructure(['client_id', 'redirect_uris']);

        $this->assertSame(1, Client::query()->where('name', 'Claude Code')->count());
    }

    public function test_consent_screen_asks_a_logged_in_user_to_authorize(): void
    {
        $user = $this->makeUser();
        $client = $this->publicClient();

        $this->get($this->authorizeUrl($client))->assertRedirect(route('login'));

        $this->actingAs($user)->get($this->authorizeUrl($client))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/OAuthAuthorize')
                ->where('client.name', 'Claude Code')
                ->where('scopes', [PassportServiceProvider::READ_SCOPE, PassportServiceProvider::WRITE_SCOPE])
                ->has('authToken')
                ->where('approveUrl', route('passport.authorizations.approve')));
    }

    public function test_user_can_disconnect_an_app_but_not_someone_elses(): void
    {
        $user = $this->makeUser();
        $stranger = $this->makeUser();
        $client = $this->publicClient();
        $token = $this->grantToken($user, $client);
        $strangerToken = $this->grantToken($stranger, $client);

        $this->actingAs($user)->get(route('user.security'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('connectedApps', 1)
                ->where('connectedApps.0.name', 'Claude Code')
                ->where('connectedApps.0.scopes', [PassportServiceProvider::READ_SCOPE]));

        $this->actingAs($user)->delete(route('user.connected-apps.destroy', $client->getKey()))->assertRedirect();

        $this->assertTrue($token->fresh()->revoked);
        $this->assertFalse($strangerToken->fresh()->revoked);
        $this->actingAs($user)->delete(route('user.connected-apps.destroy', $client->getKey()))->assertNotFound();
    }

    private function makeUser(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->forceFill(['current_team_id' => $user->ownedTeams()->first()->id])->save();

        return $user->fresh();
    }

    private function publicClient(): Client
    {
        return app(ClientRepository::class)->createAuthorizationCodeGrantClient('Claude Code', ['http://localhost:33418/callback'], confidential: false);
    }

    private function grantToken(User $user, Client $client): Token
    {
        return Token::query()->forceCreate([
            'id' => Str::random(80),
            'user_id' => $user->id,
            'client_id' => $client->getKey(),
            'name' => null,
            'scopes' => [PassportServiceProvider::READ_SCOPE],
            'revoked' => false,
            'expires_at' => now()->addDays(30),
        ]);
    }

    private function authorizeUrl(Client $client): string
    {
        $verifier = Str::random(64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        return '/oauth/authorize?'.http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => 'http://localhost:33418/callback',
            'response_type' => 'code',
            'scope' => PassportServiceProvider::READ_SCOPE.' '.PassportServiceProvider::WRITE_SCOPE,
            'state' => 'state-123',
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ]);
    }
}
