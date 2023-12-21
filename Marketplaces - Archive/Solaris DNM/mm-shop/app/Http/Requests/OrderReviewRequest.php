<?php

namespace App\Http\Requests;

use App\Order;
use Illuminate\Foundation\Http\FormRequest;

class OrderReviewRequest extends FormRequest
{
    /** @var Order $order */
    public $order;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (!$this->order) {
            return false;
        }

        if(!in_array($this->order->status, [Order::STATUS_PROBLEM, Order::STATUS_PAID, Order::STATUS_FINISHED])) {
            return false;
        }

        if($this->order->review && ($this->order->review->getEditRemainingTime() < 0 || $this->order->review->getLastEditTime() < config('mm2.review_edit_time_every'))) {
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
        $this->order = \Auth::user()->orders()->find($this->route('orderId'));
        $review = $this->order->review;

        $shop_rating = 'required|numeric|between:1,5';
        $dropman_rating = 'required|numeric|between:1,5';
        $item_rating = 'required|numeric|between:1,5';

        if($this->order->status === Order::STATUS_FINISHED && $review) {
            $shop_rating = 'required|numeric|between:'.($review->shop_rating).',5';
            $dropman_rating = 'required|numeric|between:'.($review->dropman_rating).',5';
            $item_rating = 'required|numeric|between:'.($review->item_rating).',5';
        }

        $rules = [
            'text' => 'required|min:10|max:300',
            'shop_rating' => $shop_rating,
            'dropman_rating' => $dropman_rating,
            'item_rating' => $item_rating,
        ];

        return $rules;
    }

    public function messages()
    {
        if($this->order->status === Order::STATUS_FINISHED && $this->order->review) {
            $messages = [
                'shop_rating.between' => 'Нельзя изменить оценку работы магазина в меньшую сторону',
                'dropman_rating.between' => 'Нельзя изменить оценку работы курьера в меньшую сторону',
                'item_rating.between' => 'Нельзя изменить оценку качества товара в меньшую сторону',
            ];
        } else {
            $messages = [
                'shop_rating.between' => __('validation.between.numeric'),
                'dropman_rating.between' => __('validation.between.numeric'),
                'item_rating.between' => __('validation.between.numeric')
            ];
        }

        $messages['text'] = __('validation.attributes.text');

        return $messages;
    }
}
