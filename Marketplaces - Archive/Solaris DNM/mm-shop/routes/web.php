<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/
Route::pattern('slug', '[A-Za-z0-9-_]*');
Route::pattern('goodId', '[0-9-_]*');
Route::pattern('packageId', '[0-9-_]*');
Route::pattern('categoryId', '[0-9-_]*');
Route::pattern('orderId', '[0-9-_]*');
Route::pattern('threadId', '[0-9-_]*');
Route::pattern('serviceId', '[0-9-_]*');
Route::pattern('employeeId', '[0-9-_]*');
Route::pattern('walletId', '[0-9-_]*');
Route::pattern('placeId', '[0-9-_]*');
Route::pattern('pageId', '[0-9-_]*');
Route::pattern('userId', '[0-9-_]*');
Route::pattern('lotId', '[0-9-_]*');
Route::pattern('distributionId', '[0-9-_]*');
Route::pattern('exchangeRequestId', '[0-9-_]*');
Route::pattern('promocodeId', '[0-9-_]*');
Route::pattern('groupId', '[0-9-_]*');
Route::pattern('pageName', '[a-z0-9_\-]*');
Route::pattern('referralSlug', '[A-Za-z0-9-_]{16}');
Route::pattern('exchangePaymentId', '[A-Za-z0-9-_]*');

Route::get ('/~/{referralSlug}', 'HomeController@referral');

Route::group(['middleware' => 'referral'], function () {
    Route::get ('/auth/login', 'Auth\LoginController@showLoginForm')->name('login');
    Route::post('/auth/login', 'Auth\LoginController@login');
    Route::get ('/auth/logout', 'Auth\LoginController@logout');
    Route::get ('/auth/2fa/otp', 'Auth\LoginController@show2FAOTPForm');
    Route::post('/auth/2fa/otp', 'Auth\LoginController@login2FAOTP');
    Route::get ('/auth/2fa/pgp', 'Auth\LoginController@show2FAPGPForm');
    Route::post('/auth/2fa/pgp', 'Auth\LoginController@login2FAPGP');

    // Registration Routes...
    Route::get ('/auth/register', 'Auth\RegisterController@showRegistrationForm');
    Route::post('/auth/register', 'Auth\RegisterController@register');
    Route::get ('/auth/pending', 'Auth\PendingController@show');
});

Route::get ('/auth/transparent', 'Auth\TransparentController@index');

// API Routes...
Route::get ('/api', 'API\APIController@index');
//Route::get ('/api/qiwi', 'API\APIController@qiwi');
//Route::post('/api/qiwi', 'API\APIController@qiwiReport');
Route::get ('/api/eos', 'API\APIController@eos');
Route::get ('/api/superuser', 'API\APIController@superuser');
Route::get ('/api/goods', 'API\APIController@goods');
Route::get ('/api/regions', 'API\APIController@regions');
Route::post('/api/add_position', 'API\APIController@addPosition');
Route::post('/api/delete_position', 'API\APIController@deletePosition');
Route::get ('/api/check_position', 'API\APIController@checkPosition');

Route::get ('/api/exchange_finish', 'API\APIController@exchangeFinish');
Route::get ('/api/exchange_cancel', 'API\APIController@exchangeCancel');

Route::get ('/api/telegram/goods', 'API\APIController@telegramGoods');
Route::post('/api/telegram/auth', 'API\APIController@telegramAuth');
Route::post('/api/telegram/auth_local', 'API\APIController@telegramAuthLocal');
Route::post('/api/telegram/buy_check', 'API\APIController@telegramBuyCheck');
Route::post('/api/telegram/buy', 'API\APIController@telegramBuy');
Route::post('/api/telegram/orders', 'API\APIController@telegramOrders');
Route::post('/api/telegram/orders/qiwi_paid', 'API\APIController@telegramOrdersQiwiPaid');
Route::post('/api/telegram/balance', 'API\APIController@telegramBalance');

Route::post('/api/exchanges', 'API\ExchangesController@handler');

Route::get ('/help/employee/{pageName?}', 'HelpController@employee');

Route::group(['middleware' => ['web', 'active', 'auth', 'referral']], function() {
//    Route::get ('/', 'HomeController@index');
//    Route::get ('/catalog', 'CatalogController@index');

    Route::get ('/orders', 'OrdersController@index');
    Route::get ('/orders/{orderId}', 'OrdersController@order');
    Route::post('/orders/{orderId}', 'OrdersController@orderQiwiPaid');
    Route::get ('/orders/review/{orderId}', 'OrdersController@showReviewForm');
    Route::post('/orders/review/{orderId}', 'OrdersController@review');
    Route::get ('/orders/problem/{orderId}', 'OrdersController@showProblemForm');
    Route::post('/orders/problem/{orderId}', 'OrdersController@problem');
    Route::get ('/orders/extend/{orderId}', 'OrdersController@acceptPreorderTimeExtend');
    Route::get ('/orders/review/edit/{orderId}', 'OrdersController@showReviewEditForm');
    Route::post('/orders/review/edit/{orderId}', 'OrdersController@reviewEdit');

    Route::get ('/messages', 'MessagesController@index');
    Route::get ('/messages/delete', 'MessagesController@showDeleteForm');
    Route::post('/messages/delete', 'MessagesController@delete');
    Route::get ('/messages/{threadId}', 'MessagesController@thread');
    Route::post('/messages/{threadId}', 'MessagesController@sendMessage');
    Route::post('/messages/delete/{threadId}', 'MessagesController@threadDelete');
    Route::get ('/messages/employee_invite', 'MessagesController@employeeInvite');

    Route::get ('/balance', 'BalanceController@index');
    Route::post('/balance', 'BalanceController@balance');
    Route::get ('/balance/redirect/{exchangePaymentId}', 'BalanceController@redirectToExchange');

    Route::get ('/exchange', 'Exchange\ExchangeController@index');
    Route::post('/exchange', 'Exchange\ExchangeController@exchange');
    Route::get ('/exchange/{exchangeRequestId}', 'Exchange\ExchangeController@request');
    Route::post('/exchange/{exchangeRequestId}', 'Exchange\ExchangeController@requestAction');
    Route::get ('/exchange/history', 'Exchange\ExchangeController@history');

    Route::get ('/exchange/register', 'Exchange\ExchangeController@showRegisterForm');
    Route::post('/exchange/register', 'Exchange\ExchangeController@register');
    Route::get ('/exchange/management', 'Exchange\ManagementController@index');
    Route::get ('/exchange/management/{exchangeRequestId}', 'Exchange\ManagementController@request');
    Route::get ('/exchange/management/finish/{exchangeRequestId}', 'Exchange\ManagementController@requestFinish');
    Route::get ('/exchange/management/cancel/{exchangeRequestId}', 'Exchange\ManagementController@requestCancel');

    Route::get ('/exchange/management/overview', 'Exchange\ManagementController@overview');
    Route::get ('/exchange/management/settings', 'Exchange\ManagementController@showSettingsForm');
    Route::post('/exchange/management/settings', 'Exchange\ManagementController@settings');
    Route::get ('/exchange/management/settings/init_test', 'Exchange\ManagementController@settingsInitTest');
    Route::get ('/exchange/management/settings/test_paid/{exchangeRequestId}', 'Exchange\ManagementController@settingsTestPaid');

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
    Route::get ('/settings/contacts', 'SettingsController@showContactsForm');
    Route::post('/settings/contacts', 'SettingsController@contacts');

    Route::get ('/referral', 'ReferralController@index');
    Route::get ('/referral/url', 'ReferralController@showUrlForm');
    Route::post('/referral/url', 'ReferralController@url');

    Route::get ('/notifications_read', 'CatalogController@notificationsRead');

    Route::get ('/shop/management', 'Shops\Management\ManagementController@index');
    Route::get ('/shop/management/init', 'Shops\Management\InitController@showInitForm');
    Route::post('/shop/management/init', 'Shops\Management\InitController@init');
    Route::get ('/shop/management/goods', 'Shops\Management\GoodsController@index');
    Route::get ('/shop/management/goods/add', 'Shops\Management\GoodsController@showAddForm');
    Route::post('/shop/management/goods/add', 'Shops\Management\GoodsController@add');
    Route::get ('/shop/management/goods/edit/{goodId}', 'Shops\Management\GoodsController@showEditForm');
    Route::post('/shop/management/goods/edit/{goodId}', 'Shops\Management\GoodsController@edit');
    Route::get ('/shop/management/goods/delete/{goodId}', 'Shops\Management\GoodsController@showDeleteForm');
    Route::post('/shop/management/goods/delete/{goodId}', 'Shops\Management\GoodsController@delete');
    Route::get ('/shop/management/goods/clone/{goodId}', 'Shops\Management\GoodsController@showCloneForm');
    Route::post('/shop/management/goods/clone/{goodId}', 'Shops\Management\GoodsController@doClone');
    Route::get ('/shop/management/goods/cities/{goodId}', 'Shops\Management\GoodsController@showCitiesForm');
    Route::post('/shop/management/goods/cities/{goodId}', 'Shops\Management\GoodsController@cities');
    Route::get ('/shop/management/goods/packages/{goodId}', 'Shops\Management\GoodsController@packages');
    Route::get ('/shop/management/goods/packages/city/{goodId}/{cityId}', 'Shops\Management\GoodsController@packagesInCity');
    Route::get ('/shop/management/goods/packages/add/{goodId}/{cityId}', 'Shops\Management\GoodsController@showPackageAddForm');
    Route::post('/shop/management/goods/packages/add/{goodId}/{cityId}', 'Shops\Management\GoodsController@packageAdd');
    Route::get ('/shop/management/goods/packages/edit/{goodId}/{packageId}', 'Shops\Management\GoodsController@showPackageEditForm');
    Route::post('/shop/management/goods/packages/edit/{goodId}/{packageId}', 'Shops\Management\GoodsController@packageEdit');
    Route::get ('/shop/management/goods/packages/delete/{goodId}/{packageId}', 'Shops\Management\GoodsController@showPackageDeleteForm');
    Route::post('/shop/management/goods/packages/delete/{goodId}/{packageId}', 'Shops\Management\GoodsController@packageDelete');
    Route::get ('/shop/management/goods/quests/{goodId}/{packageId}', 'Shops\Management\GoodsController@quests');
    Route::get ('/shop/management/goods/quests/add/{goodId}', 'Shops\Management\GoodsController@showQuestsAddCityForm');
    Route::get ('/shop/management/goods/quests/add/{goodId}/{cityId}', 'Shops\Management\GoodsController@showQuestAddForm');
    Route::post('/shop/management/goods/quests/add/{goodId}/{cityId}', 'Shops\Management\GoodsController@questAdd');
    Route::get ('/shop/management/goods/quests/edit/{goodId}/{questId}', 'Shops\Management\GoodsController@showQuestEditForm');
    Route::post('/shop/management/goods/quests/edit/{goodId}/{questId}', 'Shops\Management\GoodsController@questEdit');
    Route::get ('/shop/management/goods/quests/view/{goodId}/{questId}', 'Shops\Management\GoodsController@questView');
    Route::get ('/shop/management/goods/quests/delete/{goodId}/{questId}', 'Shops\Management\GoodsController@showQuestDeleteForm');
    Route::post('/shop/management/goods/quests/delete/{goodId}/{questId}', 'Shops\Management\GoodsController@questDelete');
    Route::get ('/shop/management/goods/quests/map', 'Shops\Management\Quests\MapController@index')->name('quests_map');
    Route::get ('/shop/management/goods/places/{goodId}/{cityId}', 'Shops\Management\GoodsController@places');
    Route::get ('/shop/management/goods/places/add/{goodId}/{cityId}', 'Shops\Management\GoodsController@showPlaceAddForm');
    Route::post('/shop/management/goods/places/add/{goodId}/{cityId}', 'Shops\Management\GoodsController@placeAdd');
    Route::get ('/shop/management/goods/places/edit/{goodId}/{placeId}/{cityId}', 'Shops\Management\GoodsController@showPlaceEditForm');
    Route::post('/shop/management/goods/places/edit/{goodId}/{placeId}/{cityId}', 'Shops\Management\GoodsController@placeEdit');
    Route::get ('/shop/management/goods/places/delete/{goodId}/{placeId}', 'Shops\Management\GoodsController@showPlaceDeleteForm');
    Route::post('/shop/management/goods/places/delete/{goodId}/{placeId}', 'Shops\Management\GoodsController@placeDelete');

    Route::get ('/shop/management/goods/services', 'Shops\Management\GoodsController@services');
    Route::get ('/shop/management/goods/services/add', 'Shops\Management\GoodsController@showServiceAddForm');
    Route::post('/shop/management/goods/services/add', 'Shops\Management\GoodsController@serviceAdd');
    Route::get ('/shop/management/goods/services/edit/{serviceId}', 'Shops\Management\GoodsController@showServiceEditForm');
    Route::post('/shop/management/goods/services/edit/{serviceId}', 'Shops\Management\GoodsController@serviceEdit');
    Route::get ('/shop/management/goods/services/delete/{serviceId}', 'Shops\Management\GoodsController@showServiceDeleteForm');
    Route::post('/shop/management/goods/services/delete/{serviceId}', 'Shops\Management\GoodsController@serviceDelete');

    Route::get ('/shop/management/goods/reviews/reply/{goodId}/{reviewId}', 'Shops\Management\GoodsController@showReviewReplyForm');
    Route::post('/shop/management/goods/reviews/reply/{goodId}/{reviewId}', 'Shops\Management\GoodsController@reviewReply');
    Route::get ('/shop/management/goods/reviews/hideToggle/{goodId}/{reviewId}', 'Shops\Management\GoodsController@reviewHideToggle');

    Route::get ('/shop/management/goods/moderation', 'Shops\Management\GoodsController@showModerationForm');
    Route::get ('/shop/management/goods/moderation/accept/{questId}', 'Shops\Management\GoodsController@moderationAccept');
    Route::get ('/shop/management/goods/moderation/decline/{questId}', 'Shops\Management\GoodsController@moderationDecline');
    Route::post('/shop/management/goods/moderation', 'Shops\Management\GoodsController@batchModeration');

    Route::get ('/shop/management/orders', 'Shops\Management\OrdersController@index');
    Route::get ('/shop/management/orders/{orderId}', 'Shops\Management\OrdersController@showOrder');
    Route::post('/shop/management/orders/{orderId}', 'Shops\Management\OrdersController@order');
    Route::get ('/shop/management/orders/reviews', 'Shops\Management\OrdersController@showReviews');
    Route::get ('/shop/management/orders/{orderId}/notfound', 'Shops\Management\OrdersController@orderNotFound');
    Route::post('/shop/management/orders/{orderId}/extendPreorderTime', 'Shops\Management\OrdersController@extendPreorderTime');

    Route::get ('/shop/management/messages', 'Shops\Management\MessagesController@index');
    Route::get ('/shop/management/messages/delete', 'Shops\Management\MessagesController@showDeleteForm');
    Route::post('/shop/management/messages/delete', 'Shops\Management\MessagesController@delete');
    Route::get ('/shop/management/messages/{threadId}', 'Shops\Management\MessagesController@thread');
    Route::post('/shop/management/messages/{threadId}', 'Shops\Management\MessagesController@sendMessage');
    Route::get ('/shop/management/messages/employee_add/{threadId}', 'Shops\Management\MessagesController@threadAddEmployee');
    Route::post('/shop/management/messages/delete/{threadId}', 'Shops\Management\MessagesController@threadDelete');
    Route::get ('/shop/management/messages/new', 'Shops\Management\MessagesController@showNewThreadForm');
    Route::post('/shop/management/messages/new', 'Shops\Management\MessagesController@newThread');
    Route::post('/shop/management/messages/invite/{threadId}', 'Shops\Management\MessagesController@threadInvite');
    Route::post('/shop/management/messages/kick/{threadId}', 'Shops\Management\MessagesController@threadKick');

    Route::get ('/shop/management/discounts', 'Shops\Management\DiscountsController@index');
    Route::get ('/shop/management/discounts/promo', 'Shops\Management\DiscountsController@promo');
    Route::get ('/shop/management/discounts/promo/add', 'Shops\Management\DiscountsController@showPromoAddForm');
    Route::post('/shop/management/discounts/promo/add', 'Shops\Management\DiscountsController@promoAdd');
    Route::get ('/shop/management/discounts/promo/edit/{promocodeId}', 'Shops\Management\DiscountsController@showPromoEditForm');
    Route::post('/shop/management/discounts/promo/edit/{promocodeId}', 'Shops\Management\DiscountsController@promoEdit');
    Route::get ('/shop/management/discounts/groups', 'Shops\Management\DiscountsController@groups');
    Route::get ('/shop/management/discounts/groups/add', 'Shops\Management\DiscountsController@showGroupsAddForm');
    Route::post('/shop/management/discounts/groups/add', 'Shops\Management\DiscountsController@groupsAdd');
    Route::get ('/shop/management/discounts/groups/edit/{groupId}', 'Shops\Management\DiscountsController@showGroupsEditForm');
    Route::post('/shop/management/discounts/groups/edit/{groupId}', 'Shops\Management\DiscountsController@groupsEdit');
    Route::get ('/shop/management/discounts/groups/delete/{groupId}', 'Shops\Management\DiscountsController@showGroupsDeleteForm');
    Route::post('/shop/management/discounts/groups/delete/{groupId}', 'Shops\Management\DiscountsController@groupsDelete');
    Route::get ('/shop/management/discounts/groups/manual/{groupId}', 'Shops\Management\DiscountsController@showGroupsManualForm');
    Route::post('/shop/management/discounts/groups/manual/{groupId}', 'Shops\Management\DiscountsController@groupsManual');
    Route::get ('/shop/management/discounts/groups/manual/delete/{groupId}/{userId}', 'Shops\Management\DiscountsController@groupsManualDelete');
    Route::get ('/shop/management/discounts/groups/master', 'Shops\Management\DiscountsController@showGroupsMasterForm');
    Route::post('/shop/management/discounts/groups/master', 'Shops\Management\DiscountsController@groupsMaster');

    Route::get ('/shop/management/employees', 'Shops\Management\EmployeesController@index');
    Route::get ('/shop/management/employees/add', 'Shops\Management\EmployeesController@showAddForm');
    Route::post('/shop/management/employees/add', 'Shops\Management\EmployeesController@add');
    Route::get ('/shop/management/employees/{employeeId}', 'Shops\Management\EmployeesController@employee');
    Route::get ('/shop/management/employees/edit/{employeeId}', 'Shops\Management\EmployeesController@showEmployeeEditForm');
    Route::post('/shop/management/employees/edit/{employeeId}', 'Shops\Management\EmployeesController@employeeEdit');
    Route::get ('/shop/management/employees/delete/{employeeId}', 'Shops\Management\EmployeesController@showEmployeeDeleteForm');
    Route::post('/shop/management/employees/delete/{employeeId}', 'Shops\Management\EmployeesController@employeeDelete');
    Route::get ('/shop/management/employees/access/goods/{employeeId}', 'Shops\Management\EmployeesController@showAccessGoodsForm');
    Route::post('/shop/management/employees/access/goods/{employeeId}', 'Shops\Management\EmployeesController@accessGoods');
    Route::get ('/shop/management/employees/access/quests/{employeeId}', 'Shops\Management\EmployeesController@showAccessQuestsForm');
    Route::post('/shop/management/employees/access/quests/{employeeId}', 'Shops\Management\EmployeesController@accessQuests');
    Route::get ('/shop/management/employees/access/sections/{employeeId}', 'Shops\Management\EmployeesController@showAccessSectionsForm');
    Route::post('/shop/management/employees/access/sections/{employeeId}', 'Shops\Management\EmployeesController@accessSections');

    Route::get ('/shop/management/finances', 'Shops\Management\FinancesController@index');
    Route::get ('/shop/management/finances/add', 'Shops\Management\FinancesController@showAddForm');
    Route::post('/shop/management/finances/add', 'Shops\Management\FinancesController@add');
    Route::get ('/shop/management/finances/edit/{walletId}', 'Shops\Management\FinancesController@showEditForm');
    Route::post('/shop/management/finances/edit/{walletId}', 'Shops\Management\FinancesController@edit');
    Route::get ('/shop/management/finances/delete/{walletId}', 'Shops\Management\FinancesController@showDeleteForm');
    Route::post('/shop/management/finances/delete/{walletId}', 'Shops\Management\FinancesController@delete');
    Route::get ('/shop/management/finances/view/{walletId}', 'Shops\Management\FinancesController@view');
    Route::get ('/shop/management/finances/send/{walletId}', 'Shops\Management\FinancesController@showSendForm');
    Route::post('/shop/management/finances/send/{walletId}', 'Shops\Management\FinancesController@send');
    Route::get ('/shop/management/finances/employee/{employeeId}', 'Shops\Management\FinancesController@employee');
    Route::get ('/shop/management/finances/employee/payout/{employeeId}', 'Shops\Management\FinancesController@showEmployeePayoutForm');
    Route::post('/shop/management/finances/employee/payout/{employeeId}', 'Shops\Management\FinancesController@employeePayout');
    Route::get ('/shop/management/finances/employee/all', 'Shops\Management\FinancesController@employeeAll');

    Route::get ('/shop/management/settings', 'Shops\Management\SettingsController@index');
    Route::get ('/shop/management/settings/appearance', 'Shops\Management\SettingsController@showAppearanceForm');
    Route::post('/shop/management/settings/appearance', 'Shops\Management\SettingsController@appearance');
    Route::get ('/shop/management/settings/appearance/deleteAvatar', 'Shops\Management\SettingsController@appearanceDeleteAvatar');
    Route::get ('/shop/management/settings/appearance/deleteBanner', 'Shops\Management\SettingsController@appearanceDeleteBanner');
    Route::get ('/shop/management/settings/blocks', 'Shops\Management\SettingsController@showBlocksForm');
    Route::post('/shop/management/settings/blocks', 'Shops\Management\SettingsController@blocks');
    Route::get ('/shop/management/settings/referral', 'Shops\Management\SettingsController@showReferralForm');
    Route::post('/shop/management/settings/referral', 'Shops\Management\SettingsController@referral');

    Route::get ('/shop/management/stats', 'Shops\Management\StatsController@index');
    Route::get ('/shop/management/stats/users', 'Shops\Management\StatsController@users');
    Route::get ('/shop/management/stats/orders', 'Shops\Management\StatsController@orders');
    Route::get ('/shop/management/stats/accounting', 'Shops\Management\StatsController@accounting');
    Route::get ('/shop/management/stats/accounting/add', 'Shops\Management\StatsController@showAccountingAddForm');
    Route::post('/shop/management/stats/accounting/add', 'Shops\Management\StatsController@accountingAdd');
    Route::get ('/shop/management/stats/accounting/edit/{lotId}', 'Shops\Management\StatsController@showAccountingEditForm');
    Route::post('/shop/management/stats/accounting/edit/{lotId}', 'Shops\Management\StatsController@accountingEdit');
    Route::get ('/shop/management/stats/accounting/{lotId}', 'Shops\Management\StatsController@accountingDistributions');
    Route::get ('/shop/management/stats/accounting/distribution/{lotId}', 'Shops\Management\StatsController@showAccountingDistributionsAddForm');
    Route::post('/shop/management/stats/accounting/distribution/{lotId}', 'Shops\Management\StatsController@accountingDistributionAdd');
    Route::get ('/shop/management/stats/accounting/distribution/edit/{lotId}/{distributionId}', 'Shops\Management\StatsController@showAccountingDistributionsEditForm');
    Route::post('/shop/management/stats/accounting/distribution/edit/{lotId}/{distributionId}', 'Shops\Management\StatsController@accountingDistributionsEdit');
    Route::get ('/shop/management/stats/filling', 'Shops\Management\StatsController@filling');
    Route::get ('/shop/management/stats/employees', 'Shops\Management\StatsController@employees', 'Shops\Management\StatsController@employees');

//    Route::get ('/shop/management/qiwi', 'Shops\Management\QiwiController@index');
//    Route::get ('/shop/management/qiwi/add', 'Shops\Management\QiwiController@showAddForm');
//    Route::post('/shop/management/qiwi/add', 'Shops\Management\QiwiController@add');
//    Route::get ('/shop/management/qiwi/edit/{walletId}', 'Shops\Management\QiwiController@showEditForm');
//    Route::post('/shop/management/qiwi/edit/{walletId}', 'Shops\Management\QiwiController@edit');
//    Route::get ('/shop/management/qiwi/delete/{walletId}', 'Shops\Management\QiwiController@showDeleteForm');
//    Route::post('/shop/management/qiwi/delete/{walletId}', 'Shops\Management\QiwiController@delete');
//    Route::get ('/shop/management/qiwi/operations', 'Shops\Management\QiwiController@operations');
//    Route::get ('/shop/management/qiwi/api', 'Shops\Management\QiwiController@showApiForm');
//    Route::post('/shop/management/qiwi/api', 'Shops\Management\QiwiController@api');

    Route::get ('/shop/management/system', 'Shops\Management\SystemController@index');
    Route::get ('/shop/management/system/payments', 'Shops\Management\SystemController@payments');
    Route::get ('/shop/management/system/payments/shop', 'Shops\Management\SystemController@showPaymentsShopForm');
    Route::post('/shop/management/system/payments/shop/pay', 'Shops\Management\SystemController@paymentsShopPay');
    Route::get ('/shop/management/system/payments/employees', 'Shops\Management\SystemController@showPaymentsEmployeesForm');
    Route::get ('/shop/management/system/payments/employees/confirm', 'Shops\Management\SystemController@showPaymentsEmployeesConfirmForm');
    Route::post('/shop/management/system/payments/employees/pay', 'Shops\Management\SystemController@paymentsEmployeesPay');
    Route::get ('/shop/management/system/integrations', 'Shops\Management\SystemController@showIntegrationsForm');
    Route::post('/shop/management/system/integrations', 'Shops\Management\SystemController@integrations');
    Route::post('/shop/management/users/note/{userId}', 'Shops\Management\UsersController@note');

    Route::get ('/shop/service/orders', 'Shops\ServiceController@orders');
    Route::get ('/shop/service/finances', 'Shops\ServiceController@finances');
    Route::post('/shop/service/finances', 'Shops\ServiceController@financesToggle');
    Route::get ('/shop/service/messages/new', 'Shops\ServiceController@showNewThreadForm');
    Route::post('/shop/service/messages/new', 'Shops\ServiceController@newThread');

    Route::get ('/shop/service/security/shop', 'Shops\Service\SecurityController@shopForm');
    Route::post('/shop/service/security/shop', 'Shops\Service\SecurityController@shopToggle');
    Route::get ('/shop/service/security/integrations', 'Shops\Service\SecurityController@integrationsForm');
    Route::post('/shop/service/security/integrations', 'Shops\Service\SecurityController@integrationsToggle');
    Route::get ('/shop/service/security/plan', 'Shops\Service\SecurityController@showPlanForm');
    Route::post('/shop/service/security/plan', 'Shops\Service\SecurityController@plan');
    Route::get ('/shop/service/security/users', 'Shops\Service\SecurityController@showUsersForm');

//    Route::get ('/shop/{slug}', 'Shops\ShopsController@shop');
//    Route::get ('/shop/{slug}/{categoryId}', 'Shops\ShopsController@shop');
//    Route::get ('/shop/{slug}/goods/{goodId}', 'Shops\ShopsController@good');
    Route::get ('/shop/{slug}/goods/{goodId}/{packageId}', 'Shops\ShopsController@showBuyForm');
    Route::post('/shop/{slug}/goods/{goodId}/{packageId}', 'Shops\ShopsController@buy');
    Route::get ('/shop/{slug}/message', 'Shops\ShopsController@showMessageForm');
    Route::post('/shop/{slug}/message', 'Shops\ShopsController@message');
//    Route::get ('/shop/{slug}/pages/{pageId}', 'Shops\ShopsController@page');
    Route::get ('/shop/{slug}/pages/add', 'Shops\ShopsController@showPageAddForm');
    Route::post('/shop/{slug}/pages/add', 'Shops\ShopsController@pageAdd');
    Route::get ('/shop/{slug}/pages/edit/{pageId}', 'Shops\ShopsController@showPageEditForm');
    Route::post('/shop/{slug}/pages/edit/{pageId}', 'Shops\ShopsController@pageEdit');
    Route::get ('/shop/{slug}/pages/delete/{pageId}', 'Shops\ShopsController@showPageDeleteForm');
    Route::post('/shop/{slug}/pages/delete/{pageId}', 'Shops\ShopsController@pageDelete');
});

Route::group(['middleware' => ['web', 'active', 'referral']], function() {
    Route::get('/', 'HomeController@index');
    Route::get('/catalog', 'CatalogController@index');
    Route::get('/shop/{slug}', 'Shops\ShopsController@shop');
    Route::get('/shop/{slug}/pages/{pageId}', 'Shops\ShopsController@page');
    Route::get('/shop/{slug}/goods/{goodId}', 'Shops\ShopsController@good');
});
