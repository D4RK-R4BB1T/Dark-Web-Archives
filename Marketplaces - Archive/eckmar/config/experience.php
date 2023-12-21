<?php

return [
    /**
     * Settings for vendor experience
     *
     * Array index is Level and Value is experience requirement
     * for example:
     *  2 => 3000
     * means that for level 2 vendor must have 3000 experience points
     *
     *
     * Level 1 must start at 0 points
     */
    'levels' => [
        1 => 0,
        2 => 30000,
        3 => 60000,
        4 => 90000,
        5 => 180000,
        6 => 360000,
        7 => 720000,
        8 => 1500000,
    ],

    /**
     * Multipliers determine how much XP is granted to/taken from vendors for each actions
     * XP is granted/taken by formulate USDvalue*multiplier
     *
     * Example:
     * product_delivered multiplier is 20
     * USD value of product is 100$
     * When vendor successfully deliver product he will receive 100*20=2000 XP
     */
    'multipliers' => [
        'product_delivered' => 10,
        'product_dispute_lost' =>20,
        // How much XP per star (given/taken based on feedback type)
        'feedback_per_star' => 2,
        // how much XP per USD value of transaction (given/taken based on feedback type)
        'feedback_per_usd' => 5,
    ]
];