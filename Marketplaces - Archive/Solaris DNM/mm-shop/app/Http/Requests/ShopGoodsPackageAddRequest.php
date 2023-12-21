<?php

namespace App\Http\Requests;

use App\City;
use App\Good;
use App\GoodsPackage;
use App\Packages\Utils\BitcoinUtils;
use App\Shop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShopGoodsPackageAddRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return \Auth::user()->shop()->enabled;
    }

    protected function getValidatorInstance()
    {
        // cleaning empty form groups
        $packages = $this->input('packages', []);
        foreach ($packages as $i => $package) {
            if (!array_filter($package)) { // if all values in collection are empty
                unset($packages[$i]);
            }
        }
        $packages = array_values($packages); // make indexes valid again
        $this->merge(['quests' => $packages, 'count' => count($packages)]); // replace count for valid

        return parent::getValidatorInstance();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        /** @var Shop $shop */
        $shop = \Auth::user()->shop();

        /** @var Good $good */
        $good = $shop->goods()->findOrFail($this->route('goodId'));

        $rules = [
            'packages.*.amount' => [
                'required',
                'numeric',
                'min:0.001',
                Rule::unique('goods_packages')->where(function($query) use ($good) {
                    $query
                        ->where('good_id', $good->id)
                        ->where('city_id', $this->route('city_id'))
                        ->where('measure', $this->get('measure'))
                        ->where('preorder', $this->has('preorder'));
                })
            ],
            'packages.*.measure' => 'required|in:' . implode(',', [
                    GoodsPackage::MEASURE_GRAM,
                    GoodsPackage::MEASURE_PIECE,
                    GoodsPackage::MEASURE_ML
                ]),
            'packages.*.price' => 'required|numeric|min:' . ($this->get('currency') == BitcoinUtils::CURRENCY_BTC ? 0.01 : 10),
            'packages.*.currency' => 'required|in:' . implode(',', [
                    BitcoinUtils::CURRENCY_RUB,
                    BitcoinUtils::CURRENCY_BTC,
                    BitcoinUtils::CURRENCY_USD
                ]),
            'packages.*.qiwi_price' => 'numeric|min:10',
            'packages.*.employee_reward' => 'numeric|min:0',
            'packages.*.employee_penalty' => 'numeric|min:0',
        ];

        for ($i = 0; $i < $this->get('count', 1); $i++) {
            if ($this->has("packages.$i.preorder")) {
                $rules["packages.$i.preorder_time"] = 'required|in:' . implode(',', [
                        GoodsPackage::PREORDER_TIME_24,
                        GoodsPackage::PREORDER_TIME_48,
                        GoodsPackage::PREORDER_TIME_72,
                        GoodsPackage::PREORDER_TIME_480
                    ]);

                if($services = $shop->services->pluck('id')->implode(',')) {
                    $rules["packages.$i.services.*"] = 'in:' . $services;
                }
            }
        }

        return $rules;
    }
}
