<?php

namespace App\Traits;


use App\Exceptions\BitcoinException;
use App\Packages\Utils\BitcoinUtils;

trait Walletable
{
    /**
     * Return real balance.
     *
     * @param string $currency
     * @return float
     */
    public function getRealBalance($currency = BitcoinUtils::CURRENCY_BTC)
    {
        return $this->primaryWallet()->getRealBalance($currency);
    }

    /**
     * @param string $currency
     * @return string
     */
    public function getHumanRealBalance($currency = BitcoinUtils::CURRENCY_BTC)
    {
        return $this->primaryWallet()->getHumanRealBalance($currency);
    }

    /**
     * Returns balance from unconfirmed transactions.
     *
     * @param string $currency
     * @return float
     */
    public function getPendingBalance($currency = BitcoinUtils::CURRENCY_BTC)
    {
        return $this->primaryWallet()->getPendingBalance($currency);
    }

    /**
     * Returns balance from real and pending balance.
     *
     * @param string $currency
     * @return float
     */
    public function getExpectedBalance($currency = BitcoinUtils::CURRENCY_BTC)
    {
        return $this->primaryWallet()->getPendingBalance($currency);
    }

    /**
     * @param string $currency
     * @return float
     */
    public function getReservedBalance($currency = BitcoinUtils::CURRENCY_BTC)
    {
        return $this->primaryWallet()->getReservedBalance($currency);
    }

    /**
     * @param string $currency
     * @return string
     */
    public function getHumanReservedBalance($currency = BitcoinUtils::CURRENCY_BTC)
    {
        return $this->primaryWallet()->getHumanReservedBalance($currency);
    }

    /**
     * Check if wallet has enough balance.
     * @param $amount
     * @param string $currency
     * @return bool
     */
    public function haveEnoughBalance($amount, $currency = BitcoinUtils::CURRENCY_BTC)
    {
        return $this->primaryWallet()->haveEnoughBalance($amount, $currency);
    }

    /**
     * Changes balance.
     *
     * @param float $amount
     * @param string $currency
     * @param string $description
     * @param array $fields
     * @return
     */
    public function balanceOperation($amount, $currency = BitcoinUtils::CURRENCY_BTC, $description = '', $fields = [])
    {
        return $this->primaryWallet()->balanceOperation($amount, $currency, $description, $fields);
    }
}