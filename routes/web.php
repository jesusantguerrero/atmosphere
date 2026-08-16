<?php

use App\Domains\Integration\Http\Controllers\ApiIntegrationController;
use App\Http\Controllers\Api\AccountApiController;
use App\Http\Controllers\Api\BillingCycleApiController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\CurrencyApiController;
use App\Http\Controllers\Api\IngredientApiController;
use App\Http\Controllers\Api\LabelApiController;
use App\Http\Controllers\Api\PayeeApiController;
use App\Http\Controllers\Api\RecipeApiController;
use App\Http\Controllers\Api\TimezonesApiController;
use App\Http\Controllers\Finance\FinanceAccountController;
use App\Http\Controllers\Finance\FinanceController;
use App\Http\Controllers\Finance\FinanceLinesController;
use App\Http\Controllers\Finance\FinanceTransactionController;
use App\Http\Controllers\Finance\PayeeManagerController;
use App\Http\Controllers\Finance\FinanceTrendController;
use App\Http\Controllers\Finance\FinancialOverviewController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\NextPaymentsController;
use App\Http\Controllers\ProfileSettingsController;
use App\Http\Controllers\Relationship\RelationshipController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\System\CalendarController;
use App\Http\Controllers\System\CalendarGoogleEventsController;
use App\Http\Controllers\System\CoreModuleController;
use App\Http\Controllers\System\DashboardController;
use App\Http\Controllers\System\NotificationController;
use App\Http\Controllers\System\OnboardingController;
use App\Http\Controllers\System\PlannerController;
use App\Http\Controllers\System\ServiceController;
use App\Http\Controllers\System\TeamInvitationController;
use App\Http\Controllers\System\UserDeviceController;
use App\Models\Setting;
use Freesgen\Atmosphere\Http\Controllers\SettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

if (config('app.env') == 'production') {
    URL::forceScheme('https');
}

$marketingLocale = function (): void {
    $supported = ['en', 'es'];
    $requested = request('lang');

    if (in_array($requested, $supported, true)) {
        session(['marketing_locale' => $requested]);
        app()->setLocale($requested);

        return;
    }

    $stored = session('marketing_locale');

    if (in_array($stored, $supported, true)) {
        app()->setLocale($stored);
    }
};

Route::get('/', function () use ($marketingLocale) {
    if (auth()->check()) {
        // Today merged into Dashboard — always land on /dashboard. The legacy
        // `landing_page='today'` Setting is now a no-op; the /today route itself
        // redirects to /dashboard for any direct navigation that survived.
        return redirect('/dashboard');
    }

    $marketingLocale();

    return view('landing');
})->name('landing');

Route::get('/pricing', function () use ($marketingLocale) {
    $marketingLocale();

    return view('pricing');
})->name('pricing');

Route::redirect('/demo', 'https://loger.neatlancer.com', 302)->name('demo');

Route::get('/open-source', function () use ($marketingLocale) {
    $marketingLocale();

    return view('open-source');
})->name('open-source');

Route::get('/privacy-policy', function () use ($marketingLocale) {
    $marketingLocale();

    return view('legal.privacy-policy');
})->name('privacy-policy');

Route::get('/terms-of-service', function () use ($marketingLocale) {
    $marketingLocale();

    return view('legal.terms-of-service');
})->name('terms-of-service');

Route::get('/sitemap.xml', function () {
    $now = now()->toAtomString();

    $urls = [
        ['loc' => route('landing'), 'lastmod' => $now, 'changefreq' => 'weekly', 'priority' => '1.0'],
        ['loc' => route('pricing'), 'lastmod' => $now, 'changefreq' => 'monthly', 'priority' => '0.8'],
        ['loc' => route('open-source'), 'lastmod' => $now, 'changefreq' => 'monthly', 'priority' => '0.7'],
        ['loc' => route('privacy-policy'), 'lastmod' => $now, 'changefreq' => 'yearly', 'priority' => '0.3'],
        ['loc' => route('terms-of-service'), 'lastmod' => $now, 'changefreq' => 'yearly', 'priority' => '0.3'],
    ];

    return response()->view('sitemap', ['urls' => $urls])
        ->header('Content-Type', 'application/xml');
})->name('sitemap');

Route::resource('onboarding', OnboardingController::class)->middleware(['auth:sanctum', 'atmosphere.unteamed', 'verified']);

// Automation Services
Route::get('/services/accept-oauth', [ServiceController::class, 'acceptOauth']);

Route::middleware(['auth:sanctum', 'verified'])->prefix('/api')->name('api.')->group(function () {
    //  accounts and transactions
    Route::apiResource('/settings', SettingsController::class, [
        'only' => ['index', 'store', 'update', 'delete'],
    ])->names([
        'index' => 'api.settings.index',
        'store' => 'api.settings.store',
        'update' => 'api.settings.update',
        'delete' => 'api.settings.delete',
    ]);

    Route::post('/billing-cycles/{billingCycle}/transactions/{transaction}/link-payments', [BillingCycleApiController::class, 'linkPayments']);
    Route::post('/billing-cycles/{billingCycle}/payments', [BillingCycleApiController::class, 'addPayment']);
    Route::apiResource('/billing-cycles', BillingCycleApiController::class);

    Route::get('/accounts/{account}/unlinked-payments', [AccountApiController::class, 'unlinkedPayments']);
    Route::get('/accounts/{account}/multi-currency-balances', [AccountApiController::class, 'getMultiCurrencyBalances']);

    Route::apiResource('/accounts', AccountApiController::class);
    Route::apiResource('/categories', CategoryApiController::class);

});

Route::group([], app_path('/Domains/Automation/routes.php'));
Route::group([], app_path('/Domains/Housing/routes.php'));
Route::group([], app_path('/Domains/LogerProfile/routes.php'));
Route::group([], app_path('/Domains/Transaction/routes.php'));
Route::group([], app_path('/Domains/Budget/routes.php'));
Route::group([], app_path('/Domains/Meal/routes.php'));
Route::group([], app_path('/Domains/Integration/routes.php'));

// Admin / backoffice — users, teams, impersonate, feature flags. Sits at
// the app root (not under a domain) since it cuts across every domain.
require __DIR__.'/admin.php';

/**
 * Guessable finance sub-URLs — the sub-nav uses the canonical route, but users
 * naturally guess `/finance/<section>` variants. Without these redirects they
 * hit the app 404 which bounces to the public marketing landing (jarring for a
 * logged-in user). Cheap redirects fix the discoverability gap.
 */
Route::redirect('/finance/budgets', '/budgets');
Route::redirect('/finance/budget', '/budgets');

Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->group(function () {
    // Header command palette — searches across resources for the current team.
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    /**
     *  Jetstream & Settings Section
     */

    // Jetstream teams invitations override
    Route::put('/team-invitations/{invitation}', [TeamInvitationController::class, 'resend'])->name('team-invitations.resend');

    // Landing page preference (before settings resource to avoid route conflict)
    Route::patch('/settings/landing-page', function (Request $request) {
        $request->validate(['landing_page' => ['required', 'in:dashboard,today']]);
        $user = $request->user();
        Setting::updateOrCreate(
            ['user_id' => $user->id, 'team_id' => $user->current_team_id, 'name' => 'landing_page'],
            ['value' => $request->landing_page]
        );

        return back();
    })->name('settings.landing-page');

    // Per-user notification delivery preferences (email + OneSignal push).
    // In-app (database) notifications are always delivered and not toggle-able.
    Route::patch('/user/notification-prefs', function (Request $request) {
        $data = $request->validate([
            'email' => ['required', 'boolean'],
            'push' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $user->notification_prefs = array_merge($user->notificationPrefs(), [
            'email' => (bool) $data['email'],
            'push' => (bool) $data['push'],
        ]);
        $user->save();

        return back();
    })->name('user.notification-prefs');

    // Settings hub — a lightweight landing page that groups every
    // settings entry into cards. Defined BEFORE the atmosphere resource
    // routes below so it wins the /settings match. The atmosphere
    // SettingsController expects InertiaController config that Loger
    // doesn't provide, so calling its `index` throws
    // ArgumentCountError; this closure sidesteps it entirely.
    Route::get('/settings', fn () => inertia('Settings/Index'))
        ->name('settings.index');

    // Remaining Settings routes (section renders + store/update/delete)
    // still go through the atmosphere SettingsController.
    Route::controller(SettingsController::class)->group(function () {
        Route::resource('/settings', SettingsController::class)
            ->except(['index']);
        Route::get('/settings/tab/{tabName}', 'index');
        Route::get('/settings/{name}', 'section');
    });

    Route::patch('/user/modules', [CoreModuleController::class, 'update'])->name('user.modules.update');

    // Profile settings split into three sibling pages (Account = Jetstream's
    // /user/profile). Security/Preferences reuse ProfileSettingsController which
    // extends Jetstream's controller for its sessions()/2FA helpers.
    Route::get('/user/security', [ProfileSettingsController::class, 'security'])->name('user.security');
    Route::get('/user/preferences', [ProfileSettingsController::class, 'preferences'])->name('user.preferences');

    Route::controller(NotificationController::class)->group(function () {
        Route::get('/notifications', 'index')->name('notifications');
        Route::put('/notifications/{notificationId}', 'update')->name('notifications.update');
        Route::patch('/notifications', 'bulkUpdate')->name('notifications.bulk-update');
    });

    /**************************************************************************************
     *                                  Dashboard Section
    ***************************************************************************************/

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    // /today merged into /dashboard — its Due Today + Upcoming widgets now render
    // in the dashboard side column. Route preserved as a redirect so old bookmarks,
    // notification links, and external integrations don't 404. `today` name kept so
    // existing `route('today')` calls keep resolving without a deprecation sweep.
    Route::get('/today', fn () => redirect()->route('dashboard'))->name('today');
    Route::get('/calendar', CalendarController::class)->name('calendar');
    Route::get('/calendar/google-events', CalendarGoogleEventsController::class)->name('calendar.google-events');
    // Inbox — the AI triage surface. Captured items (receipts, statements,
    // quick notes) land here and get classified into finance/reminders/chores.
    // Phase 1 renders the destination; capture + classification wiring follows.
    Route::get('/inbox', InboxController::class)->name('inbox');
    Route::post('/planner/bulk', [PlannerController::class, 'bulkStore'])->name('planner.bulk');
    Route::post('/planner', [PlannerController::class, 'store'])->name('planner.store');
    Route::put('/planner/{planner}', [PlannerController::class, 'update'])->name('planner.update');
    Route::delete('/planner/{planner}', [PlannerController::class, 'destroy'])->name('planner.destroy');
    Route::patch('/planner/{planner}/complete', [PlannerController::class, 'complete'])->name('planner.complete');
    Route::patch('/planner/{planner}/cancel', [PlannerController::class, 'cancel'])->name('planner.cancel');

    /**************************************************************************************
      *                               Finance Section
     ***************************************************************************************/
    // Finance dashboard related routes
    Route::controller(FinanceController::class)->group(function () {
        Route::get('/finance', 'index')->name('finance.overview');
    });

    // Accounts
    Route::resource('/finance/accounts', FinanceAccountController::class, [
        'as' => 'finance',
    ]);
    Route::post('/finance/accounts/{account}/import-pdf', [FinanceAccountController::class, 'importPdf'])->name('finance.accounts.import-pdf');
    Route::post('/finance/accounts/{account}/import-csv', [FinanceAccountController::class, 'importCsv'])->name('finance.accounts.import-csv');
    Route::patch('/finance/accounts/{account}/bank-code', [FinanceAccountController::class, 'updateBankCode'])->name('finance.accounts.bank-code');

    Route::resource('/finance/lines', FinanceLinesController::class, [
        'as' => 'finance',
    ]);

    // Transactions
    Route::controller(PayeeManagerController::class)->group(function () {
        Route::get('/finance/payees', 'index')->name('finance.payees');
        Route::patch('/finance/payees/{payee}', 'update')->name('finance.payees.update');
        Route::post('/finance/payees/{payee}/merge', 'merge')->name('finance.payees.merge');
        Route::delete('/finance/payees/{payee}', 'destroy')->name('finance.payees.destroy');
    });

    Route::controller(FinanceTransactionController::class)->group(function () {
        Route::post('/linked-drafts', 'findLinkedDrafts')->name('finance.transactions.linked-drafts');
        Route::patch('/transactions/{transaction}/linked', 'findLinked')->name('finance.transactions.linked');
        Route::get('/api/finance/transactions', 'list')->name('finance.transactions.list');
        Route::get('/finance/transactions', 'index')->name('finance.transactions');
        Route::post('/finance/transactions/bulk/delete', 'bulkDelete')->name('finance.transactions.bulk-delete');
        Route::get('/finance/transactions/export/csv', 'exportCsv')->name('finance.transactions.export-csv');
        Route::get('/finance/transactions/export/pdf', 'exportPdf')->name('finance.transactions.export-pdf');
        Route::get('/finance/transactions/{state}', 'getByState')->name('finance.transactions.states');
        Route::post('/finance/import', 'import')->name('finance.import');
        Route::get('/finance/export', 'export')->name('finance.export');
        Route::patch('/finance/transactions/{id}/mark-as-paid', 'markPlannedAsPaid')->name('transactions.mark-as-paid');
        Route::post('/finance/transactions', 'addPlanned')->name('transactions.store-planned');
        Route::post('/finance/transactions/{transaction}/approve', 'approve')->name('finance.transactions.approve');
        Route::post('/transactions/remove-all-drafts', 'removeAllDrafts')->name('transactions.remove-all-drafts');
        Route::post('/transactions/approve-all-drafts', 'approveAllDrafts')->name('transactions.approve-all-drafts');
    });

    // Next Payments
    Route::controller(NextPaymentsController::class)->group(function () {
        Route::get('/api/next-payments', 'index')->name('next-payments.index');
        Route::patch('/api/next-payments/{paymentId}/mark-as-paid', 'markAsPaid')->name('next-payments.mark-as-paid');
    });

    // Trends
    Route::get('/trends', [FinanceTrendController::class, 'index'])->name('finance.trends');
    Route::get('/trends/financial-overview', [FinancialOverviewController::class, 'index'])->name('finance.financial-overview');
    Route::post('/trends/financial-overview/settings', [FinancialOverviewController::class, 'updateSettings'])->name('finance.financial-overview.settings');
    Route::post('/trends/financial-overview/pinned-goals', [FinancialOverviewController::class, 'updatePinnedGoals'])->name('finance.financial-overview.pinned-goals');
    Route::post('/trends/financial-overview/goal-account-links', [FinancialOverviewController::class, 'updateGoalAccountLinks'])->name('finance.financial-overview.goal-account-links');
    Route::post('/trends/financial-overview/goal-category-links', [FinancialOverviewController::class, 'updateGoalCategoryLinks'])->name('finance.financial-overview.goal-category-links');
    Route::get('/trends/{name}', [FinanceTrendController::class, 'index'])->name('finance.trend-section');

    /**************************************************************************************
     *                               Planners (preview, frontend-only)
    ***************************************************************************************/
    Route::get('/finance/planners/house-buyer', function () {
        return Inertia::render('Finance/Planners/HouseBuyer');
    })->name('finance.planners.house-buyer');

    /**************************************************************************************
     *                               Extras Section
    ***************************************************************************************/

    Route::post('/users/{user}/devices', [UserDeviceController::class, 'store']);
    Route::get('/users/{user}/devices', [UserDeviceController::class, 'index']);
    Route::get('/relationships', [RelationshipController::class, 'index'])->name('relationships.index');
    Route::post('/relationships', [RelationshipController::class, 'store'])->name('relationships.store');
    Route::post('/relationships/{occurrence}/mark-as-occurred', [RelationshipController::class, 'markAsOccurred'])->name('relationships.mark-as-occurred');
    // Automation Services
    Route::post('/services/google', [ServiceController::class, 'google']);
    Route::get('/services/messages', [ServiceController::class, 'getMessages']);
});

/**************************************************************************************
 *                                  API Section
 ***************************************************************************************/

Route::middleware(['auth:sanctum'])->prefix('/api')->name('api.')->group(function () {
    // timezones
    Route::get('/timezones', [TimezonesApiController::class, 'index']);
    // currencies
    Route::get('/currencies', [CurrencyApiController::class, 'index']);
    Route::get('/category-transactions/{category}', [FinanceLinesController::class, 'getTransactions']);
    Route::get('/category-transactions/{category}/details', [FinanceLinesController::class, 'getDetails']);

    // Jetstream teams rewrite
    Route::patch('/v2/team-invitations/{invitation}', [TeamInvitationController::class, 'accept'])->name('team-invitations.accept-internal');
    Route::delete('/v2/team-invitations/{invitation}', [TeamInvitationController::class, 'reject'])->name('team-invitations.reject');
});

Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->prefix('/api')->name('api.')->group(function () {

    Route::apiResource('integrations', ApiIntegrationController::class);

    Route::get('/services/messages', [ServiceController::class, 'getMessages']);

    //  accounts and transactions
    Route::resource('payees', PayeeApiController::class);
    Route::patch('/accounts', [AccountApiController::class,  'bulkUpdate']);
    Route::resource('categories', CategoryApiController::class);
    Route::patch('/categories', [CategoryApiController::class,  'bulkUpdate']);

    //  recipes & ingredients
    Route::resource('recipes', RecipeApiController::class);
    Route::controller(IngredientApiController::class)->group(function () {
        Route::resource('ingredients', IngredientApiController::class);
        Route::post('/ingredients/{id}/labels', 'addLabel')->name('ingredients.label.add');
    });

    // Labels
    Route::resource('labels', LabelApiController::class);
});
