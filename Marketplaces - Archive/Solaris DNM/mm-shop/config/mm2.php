<?php
/**
 * File: mm2.php
 * This file is part of MM2 project.
 * Do not modify if you do not know what to do.
 * 2016.
 */
return [
    'application_id' => env('APP_ID', 'ABCDEF'), // id приложения, должно быть уникально для каждого инстанса приложения на сервере,
    'application_api_key' => env('APP_API_KEY', 'APIKEY'), // ключ для доступа к API магазина
    'application_onion_url' => env('APP_ONION_URL', 'http://localhost'), // адрес приложения в .onion
    'application_referral_url' => env('APP_REFERRAL_URL', 'http://localhost'), // адрес реферальной системы
    'bitcoind_rpc_host' => env('BITCOIN_RPC_HOST', '127.0.0.1'),
    'bitcoind_rpc_port' => env('BITCOIN_RPC_PORT', 18333),
    'bitcoind_rpc_user' => env('BITCOIN_RPC_USER', ''),
    'bitcoind_rpc_password' => env('BITCOIN_RPC_PASSWORD', ''),
    'tord_host' => env('TOR_HOST', '127.0.0.1'),
    'tord_port' => env('TOR_PORT', 9050),
    'local_sync' => env('LOCAL_SYNC', false),
    'local_sync_url' => env('LOCAL_SYNC_URL', 'catalog.localhost'),
    'highcharts_host' => env('HIGHCHARTS_HOST', '127.0.0.1'),
    'highcharts_port' => env('HIGHCHARTS_PORT', 18444),
    'catalog_encryption_key' => base64_decode('MlLOSekaAtCsLfMUczm+lblKNNeXHHou7NrnslKp+l0='),
    'catalog_default_sync_server' => env('CATALOG_DEFAULT_SYNC_HOST', 'solsyncrzjwl34rg.onion'),
    'catalog_default_auth_server' => env('CATALOG_DEFAULT_AUTH_HOST', 'slrsmrklzc6xnokp.onion'),
    'catalog_default_url' => env('CATALOG_DEFAULT_URL', ''),
    'exchanges_encryption_key' => '240b7b2bb27db58ba00458139011a3e3',
    'exchanges_api_url' => env('EXCHANGES_API_URL', 'http://solex5sajzypktwou3ca6ysx2et355mzhjtnuv5fsdiuk6cutgs2pkqd.onion'),
    'exchanges_only_beta' => false,

    'application_title' => env('APP_TITLE', ''), // название сайта в title
    'header_title' => env('APP_HEADER_TITLE', ''), // название сайта в шапке
    'footer_title' => env('APP_FOOTER_TITLE', NULL), // текст в футере

    'confirmations_amount' => 2, // количество подтверждений, необходимое для проведения транзакций
    'rates_cache_expires_at' => 30, // сколько максимум минут может жить курс до того, как платёжка отключится

    'shop_usd_price' => 300, // цена создания шопа
    'shop_usd_price_approx' => 0.97, // на случай, если курс поменяется за время проведения транзакции
    'shop_fee' => env('PLANS_FEE', 0.03), // % от стоимости в качестве комиссии системы,

    'referral_urls_count' => 5, // максимальное кол-во ссылок для одного пользователя в реферальной системе

    'guarantee_fee' => 0.10, // % от стоимости товара для оплаты при использовании гаранта

    'order_quest_time' => 72, // сколько часов показывать квест после покупки
    'order_close_time' => 72, // через сколько часов автоматически закрывать заказ после выдачи
    'order_problem_close_time' => 168, // через сколько часов автоматически закрывать проблемный заказ
    'order_reserve_time' => 15, // сколько минимум минут резервируется заказ
    'order_delete_time' => 24 * 60, // через сколько полностью удалять заказ

    'qiwi_balance_check_time' => 20, // раз во сколько минут отдавать задания на проверку баланса киви
    'qiwi_balance_expires_at' => 20, // сколько максимум минут может жить баланс до того, как киви кошелек будет перестанет принимать платежи

    'exchange_api_fee' => 0.01, // % от стоимости обмена при использовании QIWI Exchange API

    'bitcoin_fee' => 0.0008, // комиссия на вывод средств
    'bitcoin_min' => 0.001, // минимальная сумма для вывода
    'bitcoin_max' => 0.4, // максимальная сумма для вывода

    'basic_rub_price' => env('PLANS_BASIC_RUB_PRICE', 15000), // стоимость базового тарифа
    'basic_employees_count' => 999, // кол-во сотрудников в базовом тарифе
    'basic_employees_usd_price' => 75, // стоимость дополнительного сотрудника на базовом тарифе
    'basic_qiwi_count' => 0, // кол-во киви кошельков на базовом тарифе
    'basic_qiwi_usd_price' => 10, // стоимость киви кошелька на базовом тарифе
    'basic_description' => 'Неограниченно дополнительных сотрудников, 15 000 руб. / месяц', // описание базового тарифа
    'advanced_rub_price' => env('PLANS_ADVANCED_RUB_PRICE', 15000), // стоимость расширенного тарифа
    'advanced_employees_count' => 999, // кол-во сотрудников на расширенном тарифе
    'advanced_employees_usd_price' => 75, // стоимость дополнительного сотрудника на расширенном тарифе
    'advanced_qiwi_count' => 0, // кол-во киви кошельков на расширенном тарифе
    'advanced_qiwi_usd_price' => 10, // стоимость киви кошелька на расширенном тарифе
    'advanced_description' => 'Неограниченно дополнительных сотрудников, 1% с каждой продажи, 15 000 руб. / месяц', // описание расширенного тарифа
    'individual_usd_price' => env('PLANS_INDIVIDUAL_USD_PRICE', 1500), // стоимость отдельного тарифа
    'individual_employees_count' => 999, // кол-во сотрудников на отдельном тарифе
    'individual_employees_usd_price' => 75, // стоимость дополнительного сотрудника на отдельном тарифе
    'individual_qiwi_count' => 0, // кол-во киви кошельков на отдельном тарифе
    'individual_qiwi_usd_price' => 10, // стоимость киви кошелька на отдельном тарифе
    'individual_description' => 'Неограниченно дополнительных сотрудников, смена цветового оформления, выделенный сервер, 1500$ / месяц', // описание отдельного тарифа
    'fee_usd_price' => 0, // стоимость тарифа с комиссией
    'fee_employees_count' => 999, // кол-во сотрудников в тарифе с комиссией
    'fee_employees_usd_price' => 75, // стоимость дополнительного сотрудника на тарифе с комиссией
    'fee_qiwi_count' => 0, // кол-во киви кошельков на тарифе с комиссией
    'fee_qiwi_usd_price' => 10, // стоимость киви кошелька на тарифе с комиссией
    'fee_description' => 'Неограниченно дополнительных сотрудников, %s%% с каждой продажи', // описание тарифа с комиссией
    'individual_fee_usd_price' => 0, // стоимость отдельного тарифа с комиссией
    'individual_fee_employees_count' => 999, // кол-во сотрудников в тарифе с комиссией
    'individual_fee_employees_usd_price' => 75, // стоимость дополнительного сотрудника на тарифе с комиссией
    'individual_fee_qiwi_count' => 0, // кол-во киви кошельков на тарифе с комиссией
    'individual_fee_qiwi_usd_price' => 10, // стоимость киви кошелька на тарифе с комиссией
    'individual_fee_description' => 'Неограниченно дополнительных сотрудников, %s%% с каждой продажи, 0$ / месяц', // описание тарифа с комиссией

    'review_edit_time' => 24, // сколько часов покупатель может редактировать отзыв
    'review_edit_time_every' => 60, // через сколько минут можно повторно поменять отзыв
    'min_keep_stats_months' => 6, // сколько хранить статистику (месяцев)

    'local_ip' => env('LOCAL_IP'), // локальный ip шопа для каталога
    'app_port' => env('APP_PORT'), // --//-- порт
    'gate_enabled' => env('GATE_ENABLED', true),

    'debug' => [
        'enable_sql_query_log' => env('APP_DEBUG_ENABLE_SQL_QUERY_LOG', false),
    ],

    'btc' => [
        'parser' => [
            'transactions' => [
                'per_page_count' => env('BTC_TRANSACTIONS_PER_PAGE', 500),
                'pages' => env('BTC_TRANSACTION_PAGES', 0),
            ],
        ],
    ],
];
