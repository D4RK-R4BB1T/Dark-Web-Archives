<?php

namespace App\Http\Requests;

use App\AccountingLot;
use App\Packages\Utils\BitcoinUtils;
use Illuminate\Foundation\Http\FormRequest;
use App\Shop;
use App\GoodsPackage;

class ShopStatsAccountingEditRequest extends ShopStatsAccountingAddRequest
{
    /** @var AccountingLot $lot */
    public $lot;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        /** @var Shop $shop */
        $shop = \Auth::user()->shop();

        $this->lot = $shop->lots()->findOrFail($this->route('lotId'));

        return [
            'amount' => 'required|numeric|min:' . ($this->lot->getTotalWeight() - $this->lot->getUnusedWeight()),
            'measure' => 'required|in:' . implode(',', [
                GoodsPackage::MEASURE_GRAM,
                GoodsPackage::MEASURE_PIECE,
                GoodsPackage::MEASURE_ML
             ]),
            'price' => 'required|numeric|min:' . ($this->get('currency') == BitcoinUtils::CURRENCY_BTC ? 0.01 : 10),
            'currency' => 'required|in:' . implode(',', [
                BitcoinUtils::CURRENCY_RUB,
                BitcoinUtils::CURRENCY_BTC,
                BitcoinUtils::CURRENCY_USD
            ]),
        ];
    }
}
