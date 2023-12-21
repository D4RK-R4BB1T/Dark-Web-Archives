<?php

namespace App\Http\Requests;

use App\Order;
use App\Packages\Utils\BitcoinUtils;
use Illuminate\Foundation\Http\FormRequest;

class OrderAddAddressRequest extends FormRequest
{
    /** @var Order */
    public $order;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $shop = \Auth::user()->shop();
        $employee = \Auth::user()->employee;

        if (!$shop->enabled || !$employee) {
            return false;
        }

        $this->order = $shop->orders()->findOrFail($this->route('orderId'));
        if ($this->order->status !== Order::STATUS_PREORDER_PAID) {
            return false;
        }

        if (!BitcoinUtils::isPaymentsEnabled()) {
            return false;
        }

        if (!\Auth::user()->can('management-quests-preorder')) {
            return false;
        }

        if (!\Auth::user()->can('management-sections-orders') && $this->order->good && !\Auth::user()->can('management-quests-create', [$this->order->good, $this->order->city])) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'quest' => 'required|min:10'
        ];
    }
}
