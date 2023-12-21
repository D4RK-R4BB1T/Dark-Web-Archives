<?php

namespace App;

use Illuminate\Support\Facades\Cache;

class AdvStatsCache
{
    private static $CACHE_KEY = 'advstats';
    private static $COUNT_LIMIT = 1000;

    public static function add(int $id, int $views = 1, int $uniques = 0, int $registrations = 0): void
    {
        $stats = Cache::get(static::$CACHE_KEY);
        if (!is_array($stats) || count($stats) > static::$COUNT_LIMIT) {
            $stats = [];
        }

        if (isset($stats[$id])) {
            $stats[$id]['views'] += $views;
            $stats[$id]['uniques'] += $uniques;
            $stats[$id]['registrations'] += $registrations;
        } else {
            $stats[$id] = [
                'views' => $views,
                'uniques' => $uniques,
                'registrations' => $registrations
            ];
        }

        Cache::forever(static::$CACHE_KEY, $stats);
    }

    public static function get(): array
    {
        $stats = Cache::get(static::$CACHE_KEY);
        if (!is_array($stats)) {
            $stats = [];
        }

        return $stats;
    }

    public static function flush()
    {
        Cache::forever(static::$CACHE_KEY, []);
    }
}