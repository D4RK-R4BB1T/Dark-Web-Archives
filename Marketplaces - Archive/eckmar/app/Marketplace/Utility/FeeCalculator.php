<?php


namespace App\Marketplace\Utility;


class FeeCalculator
{
    /**
     * Decimal fee
     *
     * @var float
     */
    private $fee;
    /**
     * Decimal rest of the fee
     *
     * @var
     */
    private $base;

    /**
     * Calculate fee part and base part of the amount
     *
     * FeeCalculator constructor.
     * @param float $sum
     */
    public function __construct(float $sum)
    {
        /**
         * Fee percent must be between 0..95
         */
        $feePercent = config('marketplace.market_fee_percent');
        $feeRation = $feePercent <= 95 && $feePercent >= 0
                        ? config('marketplace.market_fee_percent') / 100
                        : .00;

        /**
         * Max 8 decimals for the fee, rounded on up
         */
        $this -> fee = round($sum * $feeRation, 8, PHP_ROUND_HALF_UP);
        /**
         * Substract fee from the sum and round to the 8  decimals for btc
         */
        $this -> base = round($sum - $this -> fee, 8, PHP_ROUND_HALF_DOWN);
    }

    /**
     * Returns fee sum that must be paid
     *
     * @return float
     */
    public function getFee($referralFee = false) : float
    {
        if($referralFee)
            return $this -> fee / 2;
        return $this -> fee;
    }

    /**
     * Get amount without fee
     *
     * @return float
     */
    public function getBase()
    {
        return $this -> base;
    }
}