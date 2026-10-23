<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;

trait RequiresRecentPassword
{
    private const PASSWORD_CONFIRMATION_MINUTES = 10;

    protected function rememberPasswordConfirmation(Request $request): void
    {
        $cacheKey = $this->passwordConfirmationCacheKey($request);

        if ($cacheKey) {
            Cache::put($cacheKey, true, now()->addMinutes(self::PASSWORD_CONFIRMATION_MINUTES));
        }
    }

    protected function hasRecentPasswordConfirmation(Request $request): bool
    {
        $cacheKey = $this->passwordConfirmationCacheKey($request);

        return $cacheKey !== null && Cache::has($cacheKey);
    }

    protected function passwordConfirmationRequired(): JsonResponse
    {
        $message = __('Password confirmation required.');

        return response()->json([
            'message' => $message,
            'errors' => ['password' => [$message]],
        ], 423);
    }

    private function passwordConfirmationCacheKey(Request $request): ?string
    {
        $token = $request->user()->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return null;
        }

        return 'mobile.password_confirmed.'.$token->getKey();
    }
}
