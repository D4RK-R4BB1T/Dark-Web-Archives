<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinancesToggleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'enabled' => 'required|boolean',
        ];
    }
}