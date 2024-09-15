<?php

namespace App\Http\Middleware;

use Inertia\Middleware;
use Tightenco\Ziggy\Ziggy;
use Illuminate\Http\Request;
use App\Concerns\Facades\Menu;
use Insane\Journal\Models\Core\Account;
use App\Domains\AppCore\Models\Category;
use Insane\Journal\Models\Core\AccountDetailType;

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
        $menu = Menu::render('app');

        return [
            ...parent::share($request),
            'ziggy' => fn () =>[
                    ...(new Ziggy)->toArray(),
                    'location' => $request->url(),
            ],
            // 'auth' => [
            //     'user' => $request->user(),
            // ],
            'locale' => app()->getLocale(),
            'settings' => $team ? $team->settings->mapWithKeys(fn ($setting) => [$setting['name'] => $setting['value']]) : [],
            'accountDetailTypes' => AccountDetailType::all(),
            'trialEndsAt' => $team ? $team->trial_ends_at : null,
            'unreadNotifications' => function () use ($user) {
                return $user ? $user->unreadNotifications->count() : 0;
            },
            'modules' => $team ? $team->modules : [],
            'menu' => $menu,
            'balance' => $team ? $team->balance() : 0,
            'accounts' => $team ? Account::getByDetailTypes($team->id) : [],
            'categories' => $team ? Category::where([
                'categories.team_id' => $team->id,
                'categories.resource_type' => 'transactions',
            ])
                ->whereNull('parent_id')
                ->orderBy('index')
                ->with('subCategories')
                ->get() : [''],
        ];
    }
}
