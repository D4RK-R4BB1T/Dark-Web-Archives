<?php

namespace App\Http\Requests;

use App\Packages\Utils\BitcoinUtils;
use App\Shop;
use App\Wallet;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ShopSystemQiwiPayRequest extends FormRequest
{
    /** @var Shop */
    public $shop;

    /** @var Wallet */
    public $wallet;

    /** @var float */
    public $qiwiPrice;

    /** @var integer */
    public $newQiwiCount;

    /** @var integer */
    public $qiwiCountDiff;

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

            if ($this->qiwiCountDiff > 0) {
                if (!$this->wallet->haveEnoughBalance($this->qiwiPrice, BitcoinUtils::CURRENCY_USD)) {
                    $validator->errors()->add('wallet', 'Недостаточно средств на кошельке.');
                    return;
                }
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

        $this->newQiwiCount = intval($this->get('qiwi_count'));
        $this->qiwiCountDiff = $this->newQiwiCount - $this->shop->qiwi_count;

        if ($this->qiwiCountDiff > 0) {
            $this->qiwiPrice =
                ($this->shop->getAdditionalQiwiWalletPrice(BitcoinUtils::CURRENCY_USD) / 30) // price for qiwi wallet per day
                * Carbon::now()->diffInDays($this->shop->expires_at) // days before shop expire
                * $this->qiwiCountDiff; // new qiwi wallets count
        }

        return [
            'qiwi_count' => 'required|numeric|min:0|max:100',
            'wallet' => (($this->qiwiCountDiff > 0) ? 'required|' : '') .
                'in:' . $this->shop->wallets()->pluck('id')->implode(',')
        ];
    }
}
