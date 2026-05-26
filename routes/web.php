<?php

use App\Http\Controllers\GroupAttributeDefinitionController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupManagerRuleController;
use App\Http\Controllers\GroupMemberController;

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

Route::namespace('Auth')->group(function () {
    Route::get('/login', 'LoginController@login')->middleware('guest')->name('login');
    Route::get('/validate', 'LoginController@validateLogin')->middleware('guest');
    Route::get('/logout', 'LoginController@logout')->middleware('auth')->name('logout');
});

Route::middleware(['auth', 'groups.manage'])->prefix('groups')->name('groups.')->group(function () {
    // Fixed segments first — must come before {group} wildcard
    Route::get('attributes', [GroupAttributeDefinitionController::class, 'index'])->name('attributes.index');
    Route::post('attributes', [GroupAttributeDefinitionController::class, 'store'])->name('attributes.store');
    Route::patch('attributes/{definition}', [GroupAttributeDefinitionController::class, 'update'])->name('attributes.update');
    Route::delete('attributes/{definition}', [GroupAttributeDefinitionController::class, 'destroy'])->name('attributes.destroy');

    Route::get('rules', [GroupManagerRuleController::class, 'overview'])->name('rules.overview');
    Route::delete('rules/{type}/{rule}', [GroupManagerRuleController::class, 'destroyFromOverview'])
        ->name('rules.overview.destroy')
        ->where('type', 'group|tag|attribute')
        ->where('rule', '[0-9]+');

    Route::get('create', [GroupController::class, 'create'])->name('create');
    Route::post('/', [GroupController::class, 'store'])->name('store');
    Route::get('/', [GroupController::class, 'index'])->name('index');

    // {group} wildcard routes — bound by slug via Group::getRouteKeyName()
    Route::get('{group}', [GroupController::class, 'show'])->name('show');
    Route::get('{group}/edit', [GroupController::class, 'edit'])->name('edit');
    Route::patch('{group}', [GroupController::class, 'update'])->name('update');
    Route::delete('{group}', [GroupController::class, 'destroy'])->name('destroy');

    Route::get('{group}/members', [GroupMemberController::class, 'index'])->name('members.index');
    Route::post('{group}/members', [GroupMemberController::class, 'store'])->name('members.store');
    Route::delete('{group}/members/{user}', [GroupMemberController::class, 'destroy'])->name('members.destroy');

    Route::get('{group}/rules', [GroupManagerRuleController::class, 'index'])->name('rules.index');
    Route::post('{group}/rules', [GroupManagerRuleController::class, 'store'])->name('rules.store');
    Route::delete('{group}/rules/{type}/{rule}', [GroupManagerRuleController::class, 'destroy'])
        ->name('rules.destroy')
        ->where('type', 'group|tag|attribute')
        ->where('rule', '[0-9]+');
});

Route::middleware(['suspended'])->group(function () {
    Route::get('/validate/dpp', 'Controller@privacy')->name('dpp');
    Route::post('/validate/dpp', 'Auth\LoginController@validatePrivacy')->name('dpp.accept');

    Route::get('/{any?}', 'Controller@index')->where('any', '.*')->name('landing');
});
 
