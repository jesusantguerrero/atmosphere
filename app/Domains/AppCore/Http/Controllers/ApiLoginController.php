<?php

namespace App\Domains\AppCore\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Throwable;

/**
 * Password login for the mobile app. Failures the user can fix answer 422 with the
 * field at fault and a stable `code` (`invalid_credentials`, `two_factor_required`,
 * `email_unverified`); anything else is reported and answers a generic 500.
 */
class ApiLoginController extends Controller
{
    public function __construct(private TwoFactorAuthenticationProvider $twoFactorProvider) {}

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return $this->failed('email', __('The provided credentials are incorrect.'), 'invalid_credentials');
            }

            if (filled($user->two_factor_secret) && $user->hasEnabledTwoFactorAuthentication() && ! $this->passesTwoFactor($user, $request)) {
                return $this->failed('code', __('A valid two factor authentication code is required.'), 'two_factor_required');
            }

            // An invitation proves the inbox is theirs: let them in to accept it, which verifies the email.
            if (! $user->hasVerifiedEmail() && $user->pendingInvitations()->isEmpty()) {
                return $this->failed('email', __('Verify your email first. Check your inbox for the link.'), 'email_unverified');
            }

            return response()->json($user->createToken($request->device_name)->plainTextToken);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => __('We could not sign you in right now. Please try again in a moment.')], 500);
        }
    }

    private function failed(string $field, string $message, string $code): JsonResponse
    {
        return response()->json(['message' => $message, 'code' => $code, 'errors' => [$field => [$message]]], 422);
    }

    /**
     * Same second factor the web login asks for: a TOTP code or a one-time recovery code.
     */
    private function passesTwoFactor(User $user, Request $request): bool
    {
        if ($code = $request->input('code')) {
            return $this->twoFactorProvider->verify(decrypt($user->two_factor_secret), $code);
        }

        $recoveryCode = $request->input('recovery_code');
        if ($recoveryCode && in_array($recoveryCode, $user->recoveryCodes(), true)) {
            $user->replaceRecoveryCode($recoveryCode);

            return true;
        }

        return false;
    }
}
