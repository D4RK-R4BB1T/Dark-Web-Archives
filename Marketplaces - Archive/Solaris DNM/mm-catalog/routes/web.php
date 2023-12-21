<?php

use Illuminate\Support\Facades\Route;

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
Route::pattern('encrypted', '[a-zA-Z0-9\=]*');

Route::get ('/', 'HomeController@index');
Route::get ('/catalog', 'CatalogController@index');
Route::get ('/pages/rules', 'PagesController@rules');
Route::get ('/catalog/jump/{encrypted}', 'CatalogController@jump');

Route::get ('/shops', 'ShopsController@index');

Route::get ('/news', 'NewsController@index');

Route::get ('/advert', 'HomeController@advert');

Route::get ('/auth/login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('/auth/login', 'Auth\LoginController@login');
Route::get ('/auth/transparent', 'Auth\TransparentController@index');

Route::get ('/auth/2fa/otp', 'Auth\LoginController@show2FAOTPForm');
Route::post('/auth/2fa/otp', 'Auth\LoginController@login2FAOTP');
Route::get ('/auth/2fa/pgp', 'Auth\LoginController@show2FAPGPForm');
Route::post('/auth/2fa/pgp', 'Auth\LoginController@login2FAPGP');

// Registration Routes...
Route::get ('/auth/register', 'Auth\RegisterController@showRegistrationForm');
Route::post('/auth/register', 'Auth\RegisterController@register');

Route::any ('/api/sync', 'APIController@sync');
Route::post('/api/advstats', 'APIController@advstats');

Route::group(['middleware' => ['web', 'auth']], function() {
    Route::get ('/auth/logout', 'Auth\LoginController@logout');

    Route::get ('/balance', 'BalanceController@index');

    Route::get ('/orders', 'OrdersController@index');
    Route::get ('/orders/{orderId}', 'OrdersController@order');

    Route::get ('/settings', 'SettingsController@index');
    Route::get ('/settings/security', 'SettingsController@showSecurityForm');
    Route::post('/settings/security', 'SettingsController@security');
    Route::get ('/settings/security/2fa/otp/enable', 'SettingsController@showSecurity2FAOTPEnableForm');
    Route::post('/settings/security/2fa/otp/enable', 'SettingsController@security2FAOTPEnable');
    Route::get ('/settings/security/2fa/otp/disable', 'SettingsController@showSecurity2FAOTPDisableForm');
    Route::post('/settings/security/2fa/otp/disable', 'SettingsController@security2FAOTPDisable');
    Route::get ('/settings/security/2fa/pgp/enable', 'SettingsController@showSecurity2FAPGPEnableForm');
    Route::post('/settings/security/2fa/pgp/enable', 'SettingsController@security2FAPGPEnable');
    Route::post('/settings/security/2fa/pgp/check', 'SettingsController@security2FAPGPCheck');
    Route::get ('/settings/security/2fa/pgp/disable', 'SettingsController@showSecurity2FAPGPDisableForm');
    Route::post('/settings/security/2fa/pgp/disable', 'SettingsController@security2FAPGPDisable');

    Route::get ('/notifications_read', 'CatalogController@notificationsRead');

    Route::get ('/ticket',     'TicketController@index');
    Route::get ('/ticket/add', 'TicketController@add_view');
    Route::post('/ticket/add', 'TicketController@add');
    Route::get ('/ticket/{ticketId}/view', 'TicketController@view');
    Route::post('/ticket/{ticketId}/comment', 'TicketController@comment');
    Route::get ('/ticket/{ticketId}/toggle', 'TicketController@toggle_status');

    Route::post('/balance/exchange', 'BalanceController@exchange');
    Route::post('/exchange_confirmation', 'BalanceController@exchangeConfirmation');
    Route::post('/fees', 'BalanceController@getPayoutFees');
    Route::get ('/balance/redirect/{exchangePaymentId}', 'BalanceController@redirectToExchange');
});

Route::group(['middleware' => ['admin']], function() {
    Route::get ('/admin', 'Admin\AdminController@redirect');

    // crud
    Route::get ('/admin/goods', 'Admin\GoodsController@index');
    Route::get ('/admin/goods/edit', 'Admin\GoodsController@edit');
    Route::post('/admin/goods/update', 'Admin\GoodsController@update');
    Route::get ('/admin/goods/create', 'Admin\GoodsController@create');
    Route::post('/admin/goods/store', 'Admin\GoodsController@store');
    Route::get ('/admin/goods/destroy', 'Admin\GoodsController@destroy');
    Route::get ('/admin/goods/view', 'Admin\GoodsController@view');

    Route::get ('/admin/categories', 'Admin\CategoriesController@index');
    Route::get ('/admin/categories/edit', 'Admin\CategoriesController@edit');
    Route::post('/admin/categories/update', 'Admin\CategoriesController@update');
    Route::get ('/admin/categories/create', 'Admin\CategoriesController@create');
    Route::post('/admin/categories/store', 'Admin\CategoriesController@store');
    Route::get ('/admin/categories/destroy', 'Admin\CategoriesController@destroy');
    Route::get ('/admin/categories/view', 'Admin\CategoriesController@view');
    /*Route::get ('/admin/orders', 'Admin\OrdersController@getIndex');
    Route::get ('/admin/edit_orders', 'Admin\OrdersController@getEdit');
    Route::post('/admin/edit_orders', 'Admin\OrdersController@postUpdate');
    Route::get ('/admin/add_orders', 'Admin\OrdersController@getCreate');
    Route::post('/admin/add_orders', 'Admin\OrdersController@postStore');
    Route::get ('/admin/delete_orders', 'Admin\OrdersController@getDestroy');
    Route::get ('/admin/view_orders', 'Admin\OrdersController@getView');*/
    Route::get ('/admin/cities', 'Admin\CitiesController@index');
    Route::get ('/admin/cities/edit', 'Admin\CitiesController@edit');
    Route::post('/admin/cities/update', 'Admin\CitiesController@update');
    Route::get ('/admin/cities/create', 'Admin\CitiesController@create');
    Route::post('/admin/cities/store', 'Admin\CitiesController@store');
    Route::get ('/admin/cities/destroy', 'Admin\CitiesController@destroy');
    Route::get ('/admin/cities/view', 'Admin\CitiesController@view');
    Route::get ('/admin/news', 'Admin\NewsController@index');
    Route::get ('/admin/news/edit', 'Admin\NewsController@edit');
    Route::post('/admin/news/update', 'Admin\NewsController@update');
    Route::get ('/admin/news/create', 'Admin\NewsController@create');
    Route::post('/admin/news/store', 'Admin\NewsController@store');
    Route::get ('/admin/news/destroy', 'Admin\NewsController@destroy');
    Route::get ('/admin/regions', 'Admin\RegionsController@getIndex');
    Route::get ('/admin/edit_regions', 'Admin\RegionsController@getEdit');
    Route::post('/admin/edit_regions', 'Admin\RegionsController@postUpdate');
    Route::get ('/admin/add_regions', 'Admin\RegionsController@getCreate');
    Route::post('/admin/add_regions', 'Admin\RegionsController@postStore');
    Route::get ('/admin/delete_regions', 'Admin\RegionsController@getDestroy');
    Route::get ('/admin/view_regions', 'Admin\RegionsController@getView');
    Route::get ('/admin/users', 'Admin\UsersController@getIndex');
    Route::get ('/admin/users/edit', 'Admin\UsersController@getEdit');
    Route::post('/admin/users/edit', 'Admin\UsersController@postUpdate');
    Route::get ('/admin/users/add', 'Admin\UsersController@getCreate');
    Route::post('/admin/users/add', 'Admin\UsersController@postStore');
    Route::get ('/admin/users/delete', 'Admin\UsersController@getDestroy');
    Route::get ('/admin/users/view', 'Admin\UsersController@getView');
    Route::get ('/admin/users/roles/view', 'Admin\RolesController@view');
    Route::post('/admin/users/roles', 'Admin\RolesController@store');
    Route::get ('/admin/users/roles/destroy', 'Admin\RolesController@destroy');
    Route::get ('/admin/shops', 'Admin\ShopsController@getIndex');
    Route::get ('/admin/edit_shops', 'Admin\ShopsController@getEdit');
    Route::post('/admin/edit_shops', 'Admin\ShopsController@postUpdate');
    Route::get ('/admin/add_shops', 'Admin\ShopsController@getCreate');
    Route::post('/admin/add_shops', 'Admin\ShopsController@postStore');
    Route::get ('/admin/delete_shops', 'Admin\ShopsController@getDestroy');
    Route::get ('/admin/view_shops', 'Admin\ShopsController@getView');
    Route::get ('/admin/stats', 'Admin\StatsController@index');
    Route::get ('/admin/disputes', 'Admin\DisputesController@index');
    Route::post ('/admin/disputes/filter', 'Admin\DisputesController@filter');

    // disable/enable shop
    Route::get ('/admin/toggle_shop', 'Admin\ShopsController@getShopToggle');

    // админка тикеты
    Route::get ('/admin/ticket', 'Admin\TicketController@index');
    Route::get ('/admin/ticket/{ticketId}/delete', 'Admin\TicketController@delete');
    Route::get ('/admin/ticket/{ticketId}/message/{msgId}/delete', 'Admin\TicketController@delete_msg');

    // Статистика рекламы
    Route::get ('/admin/advstats', 'Admin\AdvStatsController@index')->name('admin_advstats');
    Route::post('/admin/advstats', 'Admin\AdvStatsController@store');
    Route::get ('/admin/advstats/{id}/edit', 'Admin\AdvStatsController@edit')->name('admin_advstats_edit');
    Route::put ('/admin/advstats/{id}', 'Admin\AdvStatsController@update');
    Route::delete('/admin/advstats/{id}', 'Admin\AdvStatsController@destroy');

    // логи
    Route::get ('/admin/logs', 'Admin\LogReaderController@index')->name('admin.log-reader.index');
    Route::get ('/admin/logs/{id}', 'Admin\LogReaderController@view')->name('admin.log-reader.view');
});
