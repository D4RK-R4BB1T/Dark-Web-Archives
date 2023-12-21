<?php

/**
 * Configuration for Bitmessage service for notifications
 */
return [

    /**
     * Enable/Disable service from .env
     */

    'enabled' => boolval(env('BITMESSAGE_ENABLED',false)),

    /**
     * Connection data
     */

    'connection' => [
        'host' => env('BITMESSAGE_HOST','localhost'),
        'port' => intval(env('BITMESSAGE_PORT',8442)),
        'username' => env('BITMESSAGE_USER','myUser'),
        'password' => env('BITMESSAGE_PASSWORD','myPassword')
    ],

    /**
     * If service is enabled marketplace address is required
     */
    'marketplace_address' => env('BITMESSAGE_ADDRESS',''),

    /**
     * Time in minutes for how long are confirmation code valid
     */
    'confirmation_valid_time' => 3,

    /**
     * How frequent can users request new confirmation code
     * Time in seconds
     */
    'confirmation_msg_frequency' => 60

];