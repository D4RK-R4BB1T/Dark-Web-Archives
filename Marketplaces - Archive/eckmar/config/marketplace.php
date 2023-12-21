<?php

return [


    /**
     * Size of the RSA Key pair used for protecting messages between users
     * Size in bits (recommended size is 4096)
     */
    'rsa_key_length' => 4096,

    /**
     * Mnemonic is used for account recovery
     * Size in words
     */
    'mnemonic_length' => 24,

    /**
     * Types used for differentiation products
     */
    'product_types' => [
        // example:
        // 'physical' => 'Physical'
    ],

    /**
     * Market fee percent out of 100, must be from 0 to 95
     */
    'market_fee_percent' => env('MARKET_FEE_PERCENT', 5),

    /**
     * Amount of USD that needs to be paid to the market in order to become vendor
     */
    'vendor_fee' => env('VENDOR_FEE', 1),

    /**
     * After how many negative feedback should vendor get "Deal with caution tag"
     */
    'vendor_dwc_tag_count' => 1,

    /**
     * Amount in USD for feedback to be considered as Low Value (will get Low Value tag but it will be still counted towards total rating)
     */
    'vendor_low_value_feedback' => 70,

    /**
     * Trusted vendor settings (both conditions must be met in order to be displayed as trusted)
     */
    'trusted_vendor' => [
        'min_lvl' => 5,
        'min_feedbacks' => 1,
        'percentage_of_feedback_positive' => 90,

    ],
    /**
     * Number of products per page, dividable by 6
     */
    'products_per_page' => 24,

    /**
     * Every number of days job will complete purchases with enough balance, you define this number here
     */
    'days_complete' => intval(env("DAYS_TO_COMPLETE_PURCHASE", 2)),

    /**
     * Messages that are older than this number of days are deleted
     */
    'days_old_messages' => intval(env("MESSAGES_DAYS_OLD", 30)),

    /**
     * How many days from marking purchase as sent passed to the point of automaticly marking purchase as delivered and releasing the funds to the vendor
     */
    'days_old_purchases' => intval(env("PURCHASES_DAYS_OLD", 60)),

    /**
     * Coins for seeder
     */
    'seeder_coins' => [
        'stb'
    ],
    /**
     * Display warning for users to disable JavaScript if they have it enabled
     */
    'js_warning' => boolval(env('MARKETPLACE_JSWARNING',false)),


    /**
     * Mirrors of the marketpalce
     */
    'mirrors' => [
        'http://marketplace.test/',
        'http://marketplace.test/',
        'http://marketplace.test/',
        'http://marketplace.test/',
        'http://marketplace.test/',
    ],
    /**
     * For how long should front page queries be cached (in minutes)
     */
    'front_page_cache' => [
        'top_vendors' => 0,
        'latest_orders' => 0,
        'rising_vendors' => 0,
        'featured_products' => 5,
    ],

];
