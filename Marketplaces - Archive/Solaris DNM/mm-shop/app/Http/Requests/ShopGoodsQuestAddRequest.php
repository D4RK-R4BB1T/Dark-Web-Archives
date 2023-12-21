<?php

namespace App\Http\Requests;

use App\City;
use App\Good;
use Illuminate\Foundation\Http\FormRequest;

class ShopGoodsQuestAddRequest extends FormRequest
{
    /** @var Good */
    protected static $good;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return \Auth::user()->shop()->enabled;
    }

    protected function getValidatorInstance()
    {
        // cleaning empty form groups
        $quests = $this->input('quests', []);
        foreach ($quests as $i => $quest) {
            if (!array_filter($quest)) { // if all values in collection are empty
                unset($quests[$i]);
            }
        }
        $quests = array_values($quests); // make indexes valid again
        $this->merge(['quests' => $quests, 'count' => count($quests)]); // replace count for valid

        return parent::getValidatorInstance();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $goodId = $this->route('goodId');
        $cityId = $this->route('cityId');
        /** @var Good $good */
        $good = Good::find($goodId);
        /** @var City $city */
        $city = City::findOrFail($cityId);

        $packageIds = $good->packages()->where('city_id', $cityId)->pluck('id')->toArray();
        $employeeIds = $good->shop()->first()->employees()->pluck('id')->toArray();

        $rules = [
            'quests.*.package' => 'sometimes|required|in:' . implode(',', $packageIds),
            'quests.*.employee' => [
                \Auth::user()->can('management-owner') ? 'required' : '',
                'in:' . implode(',', $employeeIds)
            ],
            'quests.*.quest' => 'sometimes|required|min:3'
        ];

        $rules['quests.*.custom_place'] = 'in:' . implode(',', $good->customPlaces()->pluck('id')->toArray());
        $rules['quests.*.custom_place_title'] = 'min:5';
        for ($i = 0; $i < $this->get('count', 1); $i++) {
            if (in_array($cityId, City::citiesWithRegions()) && !empty($this->input("quests.$i.custom_place_title"))) {
                $rules["quests.$i.region"] = 'required|in:' . implode(',', $city->regions()->pluck('id')->toArray());
            }
        }

        return $rules;
    }
}
