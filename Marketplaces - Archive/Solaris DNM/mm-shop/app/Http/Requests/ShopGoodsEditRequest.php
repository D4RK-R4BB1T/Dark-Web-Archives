<?php

namespace App\Http\Requests;

use App\Category;
use Illuminate\Foundation\Http\FormRequest;
use Intervention\Image\ImageManager;

class ShopGoodsEditRequest extends ShopGoodsAddRequest
{
    public function rules()
    {
        $rules = parent::rules();
        $rules['image'] = str_replace('required', '', $rules['image']); // make image not required
        return $rules;
    }
}
