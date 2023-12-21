<?php

namespace App\Http\Requests;

use App\Packages\Utils\BitcoinUtils;
use Illuminate\Foundation\Http\FormRequest;

class ShopPaidServiceAddRequest extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|min:5',
            'price' => 'required|numeric|min:10',
            'currency' => 'required|in:' . implode(',', [
                    BitcoinUtils::CURRENCY_RUB,
                    BitcoinUtils::CURRENCY_BTC,
                    BitcoinUtils::CURRENCY_USD
                ]),
        ];
    }
}
