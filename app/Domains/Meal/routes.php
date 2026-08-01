<?php

use App\Http\Controllers\Meal\IngredientController;
use App\Http\Controllers\Meal\MealController;
use App\Http\Controllers\Meal\MealMenuController;
use App\Http\Controllers\Meal\MealPlannerController;
use App\Http\Controllers\Meal\MealShoppingListController;
use App\Http\Controllers\Meal\SharedShoppingListController;
use App\Http\Controllers\RoutineController;
use App\Http\Controllers\ShoppingListController;
use Illuminate\Support\Facades\Route;

// Public shared list routes (no auth required) — token IS the auth.
Route::get('/shared/list/{token}', [SharedShoppingListController::class, 'show'])->name('shared.list');
Route::post('/shared/list/{token}/toggle/{item}', [SharedShoppingListController::class, 'toggleItem'])->name('shared.list.toggle');
Route::post('/shared/list/{token}/items', [SharedShoppingListController::class, 'addItem'])->name('shared.list.add');
Route::post('/shared/list/{token}/reset', [SharedShoppingListController::class, 'resetTrip'])->name('shared.list.reset');

// Authed chat-style shopping list (chat-style mobile UI on top of Plan module).
Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified'])->group(function () {
    Route::get('/shopping', [ShoppingListController::class, 'index'])->name('shopping.index');
    Route::get('/shopping/current', [ShoppingListController::class, 'current'])->name('shopping.current');
    Route::get('/shopping/lists', [ShoppingListController::class, 'lists'])->name('shopping.lists');
    Route::post('/shopping/lists', [ShoppingListController::class, 'createList'])->name('shopping.lists.create');
    Route::post('/shopping/import', [ShoppingListController::class, 'import'])->name('shopping.import');
    Route::post('/shopping/{plan}/items', [ShoppingListController::class, 'addItem'])->name('shopping.items.add');
    Route::post('/shopping/{plan}/stages', [ShoppingListController::class, 'addStage'])->name('shopping.stages.add');
    Route::put('/shopping/{plan}', [ShoppingListController::class, 'renameList'])->name('shopping.rename');
    Route::delete('/shopping/{plan}', [ShoppingListController::class, 'deleteList'])->name('shopping.delete');
    Route::post('/shopping/{plan}/items/{item}/cycle', [ShoppingListController::class, 'cycleItem'])->name('shopping.items.cycle');
    Route::delete('/shopping/{plan}/items/{item}', [ShoppingListController::class, 'destroyItem'])->name('shopping.items.destroy');
    Route::post('/shopping/{plan}/reset', [ShoppingListController::class, 'reset'])->name('shopping.reset');
    Route::post('/shopping/{plan}/share', [ShoppingListController::class, 'toggleShare'])->name('shopping.share');

    // Weekly routine (time-block planner) — Plan-based, no new table.
    Route::get('/housing/routine', [RoutineController::class, 'index'])->name('routine.index');
    Route::get('/housing/routine/current', [RoutineController::class, 'current'])->name('routine.current');
    Route::post('/housing/routine/{plan}/blocks', [RoutineController::class, 'storeBlock'])->name('routine.blocks.store');
    Route::put('/housing/routine/{plan}/blocks/{item}', [RoutineController::class, 'updateBlock'])->name('routine.blocks.update');
    Route::delete('/housing/routine/{plan}/blocks/{item}', [RoutineController::class, 'destroyBlock'])->name('routine.blocks.destroy');
});

Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified', 'loger.concerns:meals'])->group(function () {
    //  Meal related routes
    Route::get('/meals/overview', MealController::class)->name('meals.overview');
    Route::controller(MealController::class)->group(function () {
        Route::resource('/meals', MealController::class);
        Route::post('/meals/add-plan', 'addPlan')->name('meals.addPlan');
        Route::get('/meals-random', 'random')->name('meals.random');
        Route::post('/meals/{meal}/toggle-favorite', 'toggleFavorite')->name('meals.toggleFavorite');
    });

    Route::controller(MealShoppingListController::class)->group(function () {
        Route::get('/shopping-list', 'index')->name('meals.shoppingList');
        Route::put('/shopping-list', 'update')->name('meals.shoppingList.update');
        Route::post('/meals/{meal}/shopping-list', 'store')->name('meals.shoppingList.add');
        Route::post('/shopping-list/generate', 'generateFromMealPlan')->name('meals.shoppingList.generate');
        Route::post('/shopping-list/share', 'share')->name('meals.shoppingList.share');
        Route::post('/shopping-list/unshare', 'unshare')->name('meals.shoppingList.unshare');
    });

    Route::controller(IngredientController::class)->group(function () {
        Route::resource('/ingredients', IngredientController::class);
    });

    Route::resource('/meal-planner', MealPlannerController::class);

    Route::controller(MealMenuController::class)->group(function () {
        Route::get('/meals/menus/templates', 'templates')->name('meals.menus.templates');
        Route::get('/meals/menus', 'index')->name('meals.menus.index');
        Route::post('/meals/menus', 'store')->name('meals.menus.store');
        Route::post('/meals/menus/{menu}/load', 'load')->name('meals.menus.load');
        Route::delete('/meals/menus/{menu}', 'destroy')->name('meals.menus.destroy');
    });
});
