<?php
/**
 * File: ShopLogger.php
 * This file is part of MM2 project.
 * Do not modify if you do not know what to do.
 * 2016.
 */

namespace App\Packages\Loggers;


use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ShopLogger extends Logger
{
    public function __construct()
    {
        parent::__construct('ShopLogger');
        $handler = new StreamHandler(storage_path() . '/logs/shops.log', Logger::toMonologLevel(config('app.log_level')));
        $handler->setFormatter(new LineFormatter(null, null, false, true));
        $this->pushHandler($handler);
    }
}