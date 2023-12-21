<?php

namespace App\Http\Requests;

use App\Packages\Utils\BitcoinUtils;
use App\Shop;
use App\Wallet;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ShopSystemShopPayRequest extends FormRequest
{
    /** @var Shop */
    public $shop;

    /** @var Wallet */
    public $wallet;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (!BitcoinUtils::isPaymentsEnabled() || !$this->shop) {
            return false;
        }

        return true;
    }

    protected function getValidatorInstance()
    {
        $validator = parent::getValidatorInstance();


        $validator->after(function($validator) {
            /** @var \Illuminate\Validation\Validator $validator */
            if ($validator->errors()->count() > 0) {
                return;
            }

            if (!$this->wallet->haveEnoughBalance($this->shop->getTotalPlanPrice(BitcoinUtils::CURRENCY_USD), BitcoinUtils::CURRENCY_USD)) {
                $validator->errors()->add('wallet', 'Недостаточно средств на кошельке.');
                return;
            }
        });

        return $validator;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $this->shop = \Auth::user()->shop();
        $this->wallet = $this->shop->wallets()->find($this->get('wallet'));

        return [
            'wallet' => 'required|' .
                'in:' . $this->shop->wallets()->pluck('id')->implode(',')
        ];
    }
}
