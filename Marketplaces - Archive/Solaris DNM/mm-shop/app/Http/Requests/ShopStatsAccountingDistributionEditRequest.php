<?php

namespace App\Http\Requests;

use App\AccountingDistribution;
use App\AccountingLot;
use App\Shop;
use Illuminate\Foundation\Http\FormRequest;

class ShopStatsAccountingDistributionEditRequest extends ShopStatsAccountingDistributionAddRequest
{
    /**
     * @var AccountingLot
     */
    public $lot;

    /** @var AccountingDistribution */
    public $distribution;

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
        $this->distribution = $this->lot->distributions()->lockForUpdate()->findOrFail($this->route('distributionId'));

        return [
            'amount' => 'required|numeric|' .
                'min:' . ($this->distribution->getTotalWeight() - $this->distribution->getAvailableWeight()) . '|' .
                'max:' . ($this->distribution->getTotalWeight() + $this->lot->getUnusedWeight())
        ];
    }
}
