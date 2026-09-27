<?php

namespace App\Domains\AppCore\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class ApiLoginController extends Controller
{
    public function __construct(private TwoFactorAuthenticationProvider $twoFactorProvider) {}

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
                'device_name' => ['required', 'string', 'max:255'],
                'code' => ['nullable', 'string'],
                'recovery_code' => ['nullable', 'string'],
            ]);

            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            if ($user->hasEnabledTwoFactorAuthentication() && ! $this->passesTwoFactor($user, $request)) {
                throw ValidationException::withMessages([
                    'code' => ['A valid two factor authentication code is required.'],
                ]);
            }

            return response()->json($user->createToken($request->device_name)->plainTextToken);
        } catch (Exception $e) {
            return response()->json([
                'status' => 402,
                'message' => $e->getMessage(),
            ], 402);
        }
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
