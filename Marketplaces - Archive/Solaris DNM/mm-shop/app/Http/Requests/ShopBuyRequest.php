<?php

namespace App\Http\Requests;

use App\Good;
use App\GoodsPackage;
use App\GoodsPosition;
use App\Packages\Utils\BitcoinUtils;
use App\PaidService;
use App\QiwiWallet;
use App\Shop;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ShopBuyRequest extends FormRequest
{
    /** @var Shop */
    public $shop;

    /** @var Good */
    public $good;

    /** @var GoodsPackage */
    public $package;

    /** @var GoodsPosition */
    public $position;

    /** @var bool */
    public $guarantee;

    /** @var PaidService[] */
    public $services;

    /** @var QiwiWallet */
    public $qiwiWallet;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $this->shop = Shop::whereSlug($this->route('slug'))->firstOrFail();
        return [
            'services' => 'array',
            'services.*' => 'in:' . $this->shop->services->pluck('id')->implode(','),
            'promocode' => 'required_if:apply_code,true'
        ];
    }
}
