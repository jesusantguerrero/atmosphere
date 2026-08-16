<?php

namespace App\Http\Middleware;

use App\Concerns\Facades\Menu;
use App\Domains\AppCore\Facades\Feature;
use App\Domains\AppCore\Models\Category;
use App\Domains\AppCore\Models\FeatureFlag;
use App\Models\Account;
use App\Domains\Transaction\Services\TransactionService;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Insane\Journal\Models\Core\AccountDetailType;
use Tightenco\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     *
     * @return string|null
     */
    public function version(Request $request)
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array
     */
    public function share(Request $request)
    {
        $user = $request->user();
        $team = $user ? $user->currentTeam : null;
        return [
            ...parent::share($request),
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            // 'auth' => [
            //     'user' => $request->user(),
            // ],
            'locale' => app()->getLocale(),
            'settings' => fn () => $team ? $team->settings->mapWithKeys(fn ($setting) => [$setting['name'] => $setting['value']]) : [],
            'accountDetailTypes' => fn () => AccountDetailType::all(),
            'trialEndsAt' => $team ? $team->trial_ends_at : null,
            'unreadNotifications' => function () use ($user) {
                return $user ? $user->unreadNotifications()->count() : 0;
            },
            // Count of DRAFT transactions (captured, awaiting confirmation).
            // Powers the Inbox nav badge — the always-visible "things to do"
            // signal, mirroring unreadNotifications.
            'pendingReviewCount' => function () use ($team) {
                return $team ? TransactionService::getDraftCount($team->id) : 0;
            },
            'flash' => fn () => $request->session()->get('flash'),
            'modules' => fn () => $team ? $team->modules : [],
            'menu' => fn () => Menu::render('app'),
            'balance' => fn () => $team ? $team->balance() : 0,
            'accounts' => fn () => $team ? Account::getByDetailTypes($team->id) : [],
            'categories' => fn () => $team ? Category::where([
                'categories.team_id' => $team->id,
                'categories.resource_type' => 'transactions',
            ])
                ->whereNull('parent_id')
                ->orderBy('index')
                ->with('subCategories')
                ->get() : [''],
            'version' => config('app.version'),
            'environment' => config('app.env'),
            // Feature flags resolved for the current user context (user > team
            // > global). Shape: { 'flag-key': true, ... }. Only currently-
            // active flags are shipped — off flags don't send a false, so
            // the payload stays small even as the flag catalog grows.
            'featureFlags' => function () use ($user) {
                if (! $user) {
                    return (object) [];
                }

                return FeatureFlag::query()
                    ->pluck('key')
                    ->mapWithKeys(fn (string $key) => [$key => Feature::activeForUser($key, $user)])
                    ->filter()
                    ->toArray() ?: (object) [];
            },
        ];
    }
}
