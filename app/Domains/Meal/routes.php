<?php

use App\Http\Controllers\Meal\IngredientController;
use App\Http\Controllers\Meal\MealController;
use App\Http\Controllers\Meal\MealMenuController;
use App\Http\Controllers\Meal\MealPlannerController;
use App\Http\Controllers\Meal\MealShoppingListController;
use App\Http\Controllers\Meal\SharedShoppingListController;
use Illuminate\Support\Facades\Route;

// Public shared list routes (no auth required)
Route::get('/shared/list/{token}', [SharedShoppingListController::class, 'show'])->name('shared.list');
Route::post('/shared/list/{token}/toggle/{item}', [SharedShoppingListController::class, 'toggleItem'])->name('shared.list.toggle');

Route::middleware(['auth:sanctum', 'atmosphere.teamed', 'verified', 'loger.concerns:meals'])->group(function () {
    //  Meal related routes
    Route::get('/meals/overview', MealController::class)->name('meals.overview');
    Route::controller(MealController::class)->group(function () {
        Route::resource('/meals', MealController::class);
        Route::post('/meals/add-plan', 'addPlan')->name('meals.addPlan');
        Route::get('/meals-random', 'random')->name('meals.random');
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
        Route::get('/meals/menus', 'index')->name('meals.menus.index');
        Route::post('/meals/menus', 'store')->name('meals.menus.store');
        Route::post('/meals/menus/{menu}/load', 'load')->name('meals.menus.load');
        Route::delete('/meals/menus/{menu}', 'destroy')->name('meals.menus.destroy');
    });
});
