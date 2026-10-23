<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RequiresRecentPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\ConfirmTwoFactorRequest;
use App\Http\Requests\Mobile\PasswordConfirmationRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Fortify;

class MobileTwoFactorController extends Controller
{
    use RequiresRecentPassword;

    public function enable(PasswordConfirmationRequest $request, EnableTwoFactorAuthentication $enable): JsonResponse
    {
        $user = $request->user();

        if ($this->isConfirmed($user)) {
            throw ValidationException::withMessages([
                'password' => [__('Two factor authentication is already enabled.')],
            ]);
        }

        $enable($user, force: true);
        $this->rememberPasswordConfirmation($request);

        $user->refresh();

        return response()->json([
            'qr_svg' => $user->twoFactorQrCodeSvg(),
            'secret' => Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
            'recovery_codes' => $user->recoveryCodes(),
        ]);
    }

    public function confirm(ConfirmTwoFactorRequest $request, ConfirmTwoFactorAuthentication $confirm): JsonResponse
    {
        $user = $request->user();

        if (! $this->isConfirmed($user)) {
            $confirm($user, $request->validated('code'));
        }

        return response()->json(['success' => true, 'two_factor_enabled' => true]);
    }

    public function recoveryCodes(Request $request): JsonResponse
    {
        if (! $this->hasRecentPasswordConfirmation($request)) {
            return $this->passwordConfirmationRequired();
        }

        if (! $this->hasTwoFactorSecret($request->user())) {
            return $this->twoFactorNotEnabled();
        }

        return response()->json(['recovery_codes' => $request->user()->recoveryCodes()]);
    }

    public function regenerateRecoveryCodes(Request $request, GenerateNewRecoveryCodes $generate): JsonResponse
    {
        if (! $this->hasRecentPasswordConfirmation($request)) {
            return $this->passwordConfirmationRequired();
        }

        if (! $this->hasTwoFactorSecret($request->user())) {
            return $this->twoFactorNotEnabled();
        }

        $generate($request->user());

        return response()->json(['recovery_codes' => $request->user()->refresh()->recoveryCodes()]);
    }

    public function disable(PasswordConfirmationRequest $request, DisableTwoFactorAuthentication $disable): JsonResponse
    {
        $disable($request->user());

        return response()->json(['success' => true, 'two_factor_enabled' => false]);
    }

    private function isConfirmed(User $user): bool
    {
        return $this->hasTwoFactorSecret($user) && $user->two_factor_confirmed_at !== null;
    }

    private function hasTwoFactorSecret(User $user): bool
    {
        return filled($user->two_factor_secret);
    }

    private function twoFactorNotEnabled(): JsonResponse
    {
        $message = __('Two factor authentication is not enabled.');

        return response()->json([
            'message' => $message,
            'errors' => ['two_factor' => [$message]],
        ], 422);
    }
}
