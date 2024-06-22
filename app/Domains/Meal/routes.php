<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Meal\MealController;
use App\Http\Controllers\Meal\IngredientController;
use App\Http\Controllers\Meal\MealPlannerController;
use App\Http\Controllers\Meal\MealShoppingListController;

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
    });

    Route::controller(IngredientController::class)->group(function () {
        Route::resource('/ingredients', IngredientController::class);
    });

    Route::resource('/meal-planner', MealPlannerController::class);
});
