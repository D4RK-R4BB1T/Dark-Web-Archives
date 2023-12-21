<?php

namespace App\Http\Requests;

use App\Packages\Utils\BitcoinUtils;
use App\Shop;
use App\Wallet;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ShopSystemEmployeesPayRequest extends FormRequest
{
    /** @var Shop */
    public $shop;

    /** @var Wallet */
    public $wallet;

    /** @var float */
    public $employeesPrice;

    /** @var integer */
    public $newEmployeesCount;

    /** @var integer */
    public $employeesCountDiff;

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

            if ($this->employeesCountDiff > 0) {
                if (!$this->wallet->haveEnoughBalance($this->employeesPrice, BitcoinUtils::CURRENCY_USD)) {
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

        $this->newEmployeesCount = intval($this->get('employees_count'));
        $this->employeesCountDiff = $this->newEmployeesCount - $this->shop->employees_count;

        if ($this->employeesCountDiff > 0) {
            $this->employeesPrice =
                ($this->shop->getAdditionalEmployeePrice(BitcoinUtils::CURRENCY_USD) / 30) // price for employee per day
                * Carbon::now()->diffInDays($this->shop->expires_at) // days before shop expire
                * $this->employeesCountDiff; // new employees count
        }

        return [
            'employees_count' => 'required|numeric|min:0|max:100',
            'wallet' => (($this->employeesCountDiff > 0) ? 'required|' : '') .
                'in:' . $this->shop->wallets()->pluck('id')->implode(',')
        ];
    }
}
