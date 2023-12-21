<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShopToggleOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'enabled' => 'required|boolean',
            'reason' => 'nullable|string|min:3|max:191',
            'enable' => 'nullable|string',
            'change_reason' => 'nullable|string',
        ];
    }
}