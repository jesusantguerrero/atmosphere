<?php

namespace App\Http\Middleware;

use App\Concerns\Facades\Menu;
use App\Domains\AppCore\Models\Category;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Insane\Journal\Models\Core\Account as CoreAccount;
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
        $menu = Menu::render('app');

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
            ],
            'ziggy' => function () use ($request) {
                return array_merge((new Ziggy)->toArray(), [
                    'location' => $request->url(),
                ]);
            },
            'locale' => app()->getLocale(),
            'accountDetailTypes' => AccountDetailType::all(),
            'trialEndsAt' => $team ? $team->trial_ends_at : null,
            'unreadNotifications' => function () use ($user) {
                return $user ? $user->unreadNotifications->count() : 0;
            },
            'menu' => $menu,
            'balance' => $team ? $team->balance() : 0,
            'accounts' => $team ? CoreAccount::getByDetailTypes($team->id) : [],
            'categories' => $team ? Category::where([
                'categories.team_id' => $team->id,
                'categories.resource_type' => 'transactions',
            ])
                ->whereNull('parent_id')
                ->orderBy('index')
                ->with('subCategories')
                ->get() : [''],
        ]);
    }
}
