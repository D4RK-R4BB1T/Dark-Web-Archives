<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SecurityServiceAddThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'receiver' => 'required|in:shop,user',
            'receiver_id' => 'required|numeric',
            'title' => 'required|min:3',
            'message' => 'required|max:20000'
        ];
    }
}