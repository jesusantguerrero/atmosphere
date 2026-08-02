<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\FeatureFlagController;
use App\Http\Controllers\Admin\MailController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;
use Lab404\Impersonate\Services\ImpersonateManager;

/*
|--------------------------------------------------------------------------
| Admin / backoffice routes
|--------------------------------------------------------------------------
|
| All routes below live behind `admin.only` (see App\Http\Middleware\
| AdminOnly). Destructive routes chain `admin.only:superadmin` for the
| stricter tier. The whole prefix is gated by the `admin-panel` feature
| flag so ops can kill-switch the panel during incidents.
|
| Impersonation uses the lab404 package's default routes registered
| under the same middleware stack.
|
*/

Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified', 'admin.only', 'feature:admin-panel'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');

        // Users
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])
            ->name('users.role')
            ->middleware('admin.only');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])
            ->name('users.destroy')
            ->middleware('admin.only:superadmin');

        // Teams
        Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
        Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show');
        Route::delete('/teams/{team}', [TeamController::class, 'destroy'])
            ->name('teams.destroy')
            ->middleware('admin.only:superadmin');

        // Feature Flags — list, detail, create, edit, delete + per-scope
        // overrides. Delete requires super_admin because dropping a flag
        // can flip production behavior globally.
        Route::get('/feature-flags', [FeatureFlagController::class, 'index'])
            ->name('feature-flags.index');
        Route::post('/feature-flags', [FeatureFlagController::class, 'store'])
            ->name('feature-flags.store');
        Route::get('/feature-flags/{featureFlag}', [FeatureFlagController::class, 'show'])
            ->name('feature-flags.show');
        Route::patch('/feature-flags/{featureFlag}', [FeatureFlagController::class, 'update'])
            ->name('feature-flags.update');
        Route::delete('/feature-flags/{featureFlag}', [FeatureFlagController::class, 'destroy'])
            ->name('feature-flags.destroy')
            ->middleware('admin.only:superadmin');
        Route::post('/feature-flags/{featureFlag}/overrides', [FeatureFlagController::class, 'storeOverride'])
            ->name('feature-flags.overrides.store');
        Route::delete('/feature-flags/{featureFlag}/overrides/{overrideId}', [FeatureFlagController::class, 'destroyOverride'])
            ->name('feature-flags.overrides.destroy');

        // Mail driver — system-wide SMTP/Mailgun/SES configuration.
        Route::get('/mail', [MailController::class, 'index'])->name('mail.index');
        Route::post('/mail', [MailController::class, 'store'])->name('mail.store');

        // Impersonation — thin wrappers around lab404's ImpersonateManager
        // so we can keep the auth middleware group consistent + name the
        // routes admin.* for Ziggy on the frontend.
        Route::post('/impersonate/{user}', function ($userId) {
            $manager = app(ImpersonateManager::class);
            $target = \App\Models\User::findOrFail($userId);

            abort_unless(auth()->user()->canImpersonate(), 403);
            abort_unless($target->canBeImpersonated(), 403);

            $manager->take(auth()->user(), $target);

            return redirect('/')->with('flash', [
                'type' => 'success',
                'message' => "Now impersonating {$target->name}.",
            ]);
        })->name('impersonate.take');

        Route::post('/impersonate/leave', function () {
            app(ImpersonateManager::class)->leave();

            return redirect()->route('admin.index')->with('flash', [
                'type' => 'success',
                'message' => 'Left impersonation session.',
            ]);
        })->name('impersonate.leave');
    });
