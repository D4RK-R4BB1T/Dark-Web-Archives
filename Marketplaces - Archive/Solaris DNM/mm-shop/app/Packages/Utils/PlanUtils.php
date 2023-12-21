<?php
/**
 * File: PlanUtils.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Packages\Utils;


use App\Shop;

class PlanUtils
{
    public static function getHumanPlanName($plan)
    {
        switch ($plan) {
            case Shop::PLAN_BASIC:
                return 'Базовый';

            case Shop::PLAN_ADVANCED:
                return 'Расширенный';

            case Shop::PLAN_INDIVIDUAL:
                return 'Отдельный магазин';

            case Shop::PLAN_FEE:
                return 'Комиссионный';

            case Shop::PLAN_INDIVIDUAL_FEE:
                return 'Комиссионный (отдельный)';

            default:
                return '(неизвестно)';
        }
    }

    public static function getPlanPrice($plan, $currency = BitcoinUtils::CURRENCY_BTC)
    {
        if (!BitcoinUtils::isPaymentsEnabled()) {
            return '-';
        }

        switch ($plan) {
            case Shop::PLAN_BASIC:
                return BitcoinUtils::convert(config('mm2.basic_rub_price'), BitcoinUtils::CURRENCY_RUB, $currency);

            case Shop::PLAN_ADVANCED:
                return BitcoinUtils::convert(config('mm2.advanced_rub_price'), BitcoinUtils::CURRENCY_RUB, $currency);

            case Shop::PLAN_INDIVIDUAL:
                return BitcoinUtils::convert(config('mm2.individual_usd_price'), BitcoinUtils::CURRENCY_USD, $currency);

            case Shop::PLAN_FEE:
                return BitcoinUtils::convert(config('mm2.fee_usd_price'), BitcoinUtils::CURRENCY_USD, $currency);

            case Shop::PLAN_INDIVIDUAL_FEE:
                return BitcoinUtils::convert(config('mm2.individual_fee_usd_price'), BitcoinUtils::CURRENCY_USD, $currency);

            default:
                return 0;
        }
    }

    public static function getPlanAvailableEmployeesCount($plan)
    {
        switch ($plan) {
            case Shop::PLAN_BASIC:
                return config('mm2.basic_employees_count');

            case Shop::PLAN_ADVANCED:
                return config('mm2.advanced_employees_count');

            case Shop::PLAN_INDIVIDUAL:
                return config('mm2.individual_employees_count');

            case Shop::PLAN_FEE:
                return config('mm2.fee_employees_count');

            case Shop::PLAN_INDIVIDUAL_FEE:
                return config('mm2.individual_fee_employees_count');

            default:
                return 0;
        }
    }

    public static function getAdditionalEmployeePrice($plan, $currency = BitcoinUtils::CURRENCY_BTC)
    {
        if (!BitcoinUtils::isPaymentsEnabled()) {
            return '-';
        }

        switch ($plan) {
            case Shop::PLAN_BASIC:
                return BitcoinUtils::convert(config('mm2.basic_employees_usd_price'), BitcoinUtils::CURRENCY_USD, $currency);

            case Shop::PLAN_ADVANCED:
                return BitcoinUtils::convert(config('mm2.advanced_employees_usd_price'), BitcoinUtils::CURRENCY_USD, $currency);

            case Shop::PLAN_INDIVIDUAL:
                return BitcoinUtils::convert(config('mm2.individual_employees_usd_price'), BitcoinUtils::CURRENCY_USD, $currency);

            case Shop::PLAN_FEE:
                return BitcoinUtils::convert(config('mm2.fee_employees_usd_price'), BitcoinUtils::CURRENCY_USD, $currency);

            case Shop::PLAN_INDIVIDUAL_FEE:
                return BitcoinUtils::convert(config('mm2.individual_fee_employees_usd_price'), BitcoinUtils::CURRENCY_USD, $currency);

            default:
                return 0;
        }
    }

    public static function getPlanAvailableQiwiWalletsCount($plan)
    {
        switch ($plan) {
            case Shop::PLAN_BASIC:
                return config('mm2.basic_qiwi_count');

            case Shop::PLAN_ADVANCED:
                return config('mm2.advanced_qiwi_count');

            case Shop::PLAN_INDIVIDUAL:
                return config('mm2.individual_qiwi_count');

            case Shop::PLAN_FEE:
                return config('mm2.fee_qiwi_count');

            case Shop::PLAN_INDIVIDUAL_FEE:
                return config('mm2.individual_fee_qiwi_count');

            default:
                return 0;
        }
    }

    public static function getAdditionalQiwiWalletPrice($plan, $currency = BitcoinUtils::CURRENCY_BTC)
    {
        if (!BitcoinUtils::isPaymentsEnabled()) {
            return '-';
        }

        switch ($plan) {
            case Shop::PLAN_BASIC:
                return BitcoinUtils::convert(config('mm2.basic_qiwi_usd_price'), BitcoinUtils::CURRENCY_USD, $currency);

            case Shop::PLAN_ADVANCED:
                return BitcoinUtils::convert(config('mm2.advanced_qiwi_usd_price'), BitcoinUtils::CURRENCY_USD, $currency);

            case Shop::PLAN_INDIVIDUAL:
                return BitcoinUtils::convert(config('mm2.individual_qiwi_usd_price'), BitcoinUtils::CURRENCY_USD, $currency);

            case Shop::PLAN_FEE:
                return BitcoinUtils::convert(config('mm2.fee_qiwi_usd_price'), BitcoinUtils::CURRENCY_USD, $currency);

            case Shop::PLAN_INDIVIDUAL_FEE:
                return BitcoinUtils::convert(config('mm2.individual_fee_qiwi_usd_price'), BitcoinUtils::CURRENCY_USD, $currency);

            default:
                return 0;
        }
    }

    /**
     * @return string
     */
    public static function getPlanDescription($plan)
    {
        switch ($plan) {
            case Shop::PLAN_BASIC:
                return config('mm2.basic_description');

            case Shop::PLAN_ADVANCED:
                return config('mm2.advanced_description');

            case Shop::PLAN_INDIVIDUAL:
                return config('mm2.individual_description');

            case Shop::PLAN_FEE:
                return sprintf(config('mm2.fee_description'), config('mm2.shop_fee') * 100);

            case Shop::PLAN_INDIVIDUAL_FEE:
                return sprintf(config('mm2.individual_fee_description'), config('mm2.shop_fee') * 100);

            default:
                return 'Неизвестный тариф (?)';
        }
    }

    public static function getFeeForOrder($plan, $orderAmount, $currency = BitcoinUtils::CURRENCY_BTC)
    {
        switch ($plan) {
            case Shop::PLAN_BASIC:
                $orderRubAmount = BitcoinUtils::convert($orderAmount, $currency, BitcoinUtils::CURRENCY_RUB);
                if ($orderRubAmount <= 200000) {
                    return $orderAmount * 0.05;
                } elseif ($orderRubAmount <= 800000) {
                    return $orderAmount * 0.03;
                } elseif ($orderRubAmount <= 1500000) {
                    return $orderAmount * 0.02;
                } else {
                    return $orderAmount * 0.015;
                }
            case Shop::PLAN_ADVANCED:
                return $orderAmount * 0.01;
            case Shop::PLAN_INDIVIDUAL:
            case Shop::PLAN_FEE:
            case Shop::PLAN_INDIVIDUAL_FEE:
                return $orderAmount * config('mm2.shop_fee');
            default:
                return 0;
        }
    }

}