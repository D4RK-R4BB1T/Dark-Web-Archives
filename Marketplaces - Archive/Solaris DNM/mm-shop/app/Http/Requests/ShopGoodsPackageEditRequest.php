<?php

namespace App\Http\Requests;

use App\Good;
use App\GoodsPackage;
use App\Packages\Utils\BitcoinUtils;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShopGoodsPackageEditRequest extends FormRequest
{
    public function authorize()
    {
        return \Auth::user()->shop()->enabled;
    }

    public function rules(): array
    {
        /** @var Shop $shop */
        $shop = \Auth::user()->shop();

        /** @var Good $good */
        $good = $shop->goods()->findOrFail($this->route('goodId'));
        $package = $good->packages()->findOrFail($this->route('packageId'));

        $rules['amount'] = [
            'required',
            'numeric',
            'min:0.001',
            Rule::unique('goods_packages')->where(function($query) use ($good, $package) {
                $query
                    ->where('id', '!=', $package->id)
                    ->where('city_id', $this->route('city_id'))
                    ->where('good_id', $good->id)
                    ->where('measure', $this->get('measure'))
                    ->where('preorder', $this->has('preorder'));
            })
        ];

        $rules['measure'] = 'required|in:' . implode(',', [
            GoodsPackage::MEASURE_GRAM,
            GoodsPackage::MEASURE_PIECE,
            GoodsPackage::MEASURE_ML
        ]);
        $rules['price'] = 'required|numeric|min:' . ($this->get('currency') == BitcoinUtils::CURRENCY_BTC ? 0.01 : 10);
        $rules['currency'] = 'required|in:' . implode(',', [
            BitcoinUtils::CURRENCY_RUB,
            BitcoinUtils::CURRENCY_BTC,
            BitcoinUtils::CURRENCY_USD
        ]);
        $rules['qiwi_price'] = 'numeric|min:10';
        $rules['employee_reward'] = 'numeric|min:0';
        $rules['employee_penalty'] = 'numeric|min:0';

        if ($this->has("preorder")) {
            $rules["preorder_time"] = 'required|in:' . implode(',', [
                GoodsPackage::PREORDER_TIME_24,
                GoodsPackage::PREORDER_TIME_48,
                GoodsPackage::PREORDER_TIME_72,
                GoodsPackage::PREORDER_TIME_480
            ]);

            if($services = $shop->services->pluck('id')->implode(',')) {
                $rules["services.*"] = 'in:' . $services;
            }
        }

        return $rules;
    }
}
