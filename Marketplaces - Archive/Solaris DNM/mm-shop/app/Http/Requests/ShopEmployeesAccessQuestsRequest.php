<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShopEmployeesAccessQuestsRequest extends FormRequest
{
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
        $shop = \Auth::user()->shop();
        return [
            'quests_allowed_goods' => 'array',
            'quests_allowed_goods.*' => 'in:' . $shop->goods->pluck('id')->implode(',')
        ];
    }
}