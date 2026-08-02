<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * System-wide mail driver configuration. Lives under /admin/mail
 * because it affects every outgoing email — password resets,
 * invitations, budget alerts — and shouldn't be user-toggleable.
 *
 * Persists via the generic Setting model with a well-known key
 * ('mail_config') scoped to team_id = null so the config is a single
 * global row rather than one-per-team.
 */
class MailController extends Controller
{
    public function index(): Response
    {
        $config = Setting::query()
            ->whereNull('team_id')
            ->where('name', 'mail_config')
            ->first();

        // Fall back to config('mail') so the form pre-fills with the
        // .env-driven values on a fresh install where nothing was ever
        // saved through the UI.
        $settingData = $config
            ? (array) json_decode($config->value, true)
            : [
                'mail_driver' => config('mail.default'),
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_port' => config('mail.mailers.smtp.port'),
                'mail_username' => config('mail.mailers.smtp.username'),
                'mail_encryption' => config('mail.mailers.smtp.encryption'),
                'mail_from_address' => config('mail.from.address'),
                'mail_from_name' => config('mail.from.name'),
            ];

        return Inertia::render('Admin/Mail', [
            'settingData' => $settingData,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mail_driver' => ['nullable', 'string'],
            'mail_host' => ['nullable', 'string'],
            'mail_port' => ['nullable', 'string'],
            'mail_username' => ['nullable', 'string'],
            'mail_password' => ['nullable', 'string'],
            'mail_encryption' => ['nullable', 'string'],
            'mail_from_address' => ['nullable', 'email'],
            'mail_from_name' => ['nullable', 'string'],
        ]);

        Setting::updateOrCreate(
            [
                'user_id' => null,
                'team_id' => null,
                'name' => 'mail_config',
            ],
            [
                'value' => json_encode($validated),
            ],
        );

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Mail configuration saved.',
        ]);
    }
}
