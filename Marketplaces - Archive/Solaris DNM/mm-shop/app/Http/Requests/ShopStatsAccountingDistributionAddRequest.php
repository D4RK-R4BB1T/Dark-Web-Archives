<?php

namespace App\Http\Requests;

use App\AccountingLot;
use App\Shop;
use Illuminate\Foundation\Http\FormRequest;

class ShopStatsAccountingDistributionAddRequest extends FormRequest
{
    /**
     * @var AccountingLot
     */
    public $lot;

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
        /** @var Shop $shop */
        $shop = \Auth::user()->shop();

        $this->lot = $shop->lots()->lockForUpdate()->findOrFail($this->route('lotId'));

        return [
            'employee' => 'required|in:' . $shop->employees()->pluck('id')->implode(','),
            'amount' => 'required|numeric|min:0.001|max:' . $this->lot->getUnusedWeight()
        ];
    }
}
