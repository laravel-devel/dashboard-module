<?php

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
|
| Here is where you can register the (admin) dashboard routes for your module.
|
*/

/**
 * Dashboard homepage
 */
Route::get('/', [
    'as' => 'index',
    'uses' => config('develdashboard.dashboard_controller') . '@index',
]);

/**
 * General site settings
 */
Route::get('/settings', [
    'as' => 'settings.edit',
    'uses' => config('develdashboard.site_settings_controller') . '@edit',
    'dashboardSidebar' => 'Site->Settings',
    'permissions' => 'site.edit_settings',
]);

Route::post('/settings', [
    'as' => 'settings.update',
    'uses' => config('develdashboard.site_settings_controller') . '@update',
    'permissions' => 'site.edit_settings',
]);

/**
 * Module Management
 */
Route::get('/modules', [
    'as' => 'modules.index',
    'uses' => 'ModulesController@index',
    'dashboardSidebar' => 'Site->Modules',
    'permissions' => 'site.manage_modules',
]);

Route::post('/modules/{alias}', [
    'as' => 'modules.toggle-enabled',
    'uses' => 'ModulesController@toggleEnabled',
    'permissions' => 'site.manage_modules',
]);
