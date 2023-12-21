<?php

namespace App\Http\Requests;

use App\Employee;
use App\Packages\Utils\BitcoinUtils;
use App\Shop;
use App\Wallet;
use Illuminate\Foundation\Http\FormRequest;

class ShopFinancesEmployeesPayoutRequest extends FormRequest
{
    /** @var Shop */
    public $shop;

    /** @var Employee */
    public $employee;

    /** @var Wallet */
    public $wallet;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (!BitcoinUtils::isPaymentsEnabled() || !$this->shop || !$this->employee) {
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

            if (!$this->wallet->haveEnoughBalance($this->get('amount'), BitcoinUtils::CURRENCY_RUB)) {
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
        $this->employee = $this->shop->employees()->find($this->route('employeeId'));
        $this->wallet = $this->shop->wallets()->find($this->get('wallet'));

        return [
            'amount' => 'required|numeric|min:0.01|max:' . $this->employee->getBalance(BitcoinUtils::CURRENCY_RUB),
            'wallet' => 'required|in:' . $this->shop->wallets()->pluck('id')->implode(',')
        ];
    }
}
