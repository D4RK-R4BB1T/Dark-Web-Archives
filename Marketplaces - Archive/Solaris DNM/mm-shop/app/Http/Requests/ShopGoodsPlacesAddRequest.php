<?php

namespace App\Http\Requests;

use App\City;
use App\Good;
use Illuminate\Foundation\Http\FormRequest;

class ShopGoodsPlacesAddRequest extends FormRequest
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
        $goodId = $this->route('goodId');
        /** @var Good $good */
        $good = Good::find($goodId);

        $rules = [
            'title' => 'required|min:3'
        ];

        if (in_array($good->city_id, City::citiesWithRegions())) {
            $rules['region'] = 'required|in:' . implode(',', $good->city->regions()->pluck('id')->toArray());
        }

        return $rules;
    }
}
