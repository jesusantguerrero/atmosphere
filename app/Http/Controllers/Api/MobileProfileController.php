<?php

namespace App\Http\Controllers\Api;

use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Controllers\Api\Concerns\RequiresRecentPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\PasswordConfirmationRequest;
use App\Http\Requests\Mobile\UpdatePasswordRequest;
use App\Http\Requests\Mobile\UpdateProfileRequest;
use App\Http\Resources\Mobile\MobileUserResource;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Jetstream\Agent;
use Laravel\Sanctum\PersonalAccessToken;

class MobileProfileController extends Controller
{
    use RequiresRecentPassword;

    public function update(UpdateProfileRequest $request, UpdateUserProfileInformation $updater): JsonResponse
    {
        $user = $request->user();

        $updater->update($user, array_merge(
            ['name' => $user->name, 'email' => $user->email],
            $request->safe()->except('photo'),
            $request->hasFile('photo') ? ['photo' => $request->file('photo')] : [],
        ));

        return response()->json(MobileUserResource::make($user->refresh()));
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $request->user()->forceFill([
            'password' => Hash::make($request->validated('password')),
        ])->save();

        return response()->json(['success' => true]);
    }

    public function confirmPassword(PasswordConfirmationRequest $request): JsonResponse
    {
        $this->rememberPasswordConfirmation($request);

        return response()->json(['success' => true]);
    }

    public function sessions(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'sessions' => $this->apiTokenSessions($user)->concat($this->browserSessions($user))->values(),
        ]);
    }

    public function revokeOtherSessions(PasswordConfirmationRequest $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        $user->tokens()
            ->when($currentToken instanceof PersonalAccessToken, fn ($query) => $query->where('id', '!=', $currentToken->getKey()))
            ->delete();

        $this->browserSessionsQuery($user)?->delete();

        return response()->json(['success' => true]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function apiTokenSessions(User $user): Collection
    {
        $currentToken = $user->currentAccessToken();

        return $user->tokens()->latest('last_used_at')->get()->map(fn (PersonalAccessToken $token) => [
            'type' => 'token',
            'device' => $token->name,
            'platform' => null,
            'browser' => null,
            'ip_address' => null,
            'last_active_at' => ($token->last_used_at ?? $token->created_at)?->toIso8601String(),
            'is_current' => $currentToken instanceof PersonalAccessToken && $currentToken->getKey() === $token->getKey(),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function browserSessions(User $user): Collection
    {
        $query = $this->browserSessionsQuery($user);

        if (! $query) {
            return collect();
        }

        return $query->orderByDesc('last_activity')->get()->map(function ($session) {
            $agent = tap(new Agent, fn (Agent $agent) => $agent->setUserAgent($session->user_agent));

            return [
                'type' => 'browser',
                'device' => $agent->isDesktop() ? 'Desktop' : 'Mobile',
                'platform' => $agent->platform(),
                'browser' => $agent->browser(),
                'ip_address' => $session->ip_address,
                'last_active_at' => Carbon::createFromTimestamp($session->last_activity)->toIso8601String(),
                'is_current' => false,
            ];
        });
    }

    private function browserSessionsQuery(User $user): ?Builder
    {
        if (config('session.driver') !== 'database') {
            return null;
        }

        return DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $user->getAuthIdentifier());
    }
}
