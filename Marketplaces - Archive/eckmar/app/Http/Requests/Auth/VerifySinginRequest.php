<?php

namespace App\Http\Requests\Auth;

use App\Exceptions\RequestException;
use App\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class VerifySinginRequest extends FormRequest
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
        return [
            'validation_string' => 'required|string|max:10'
        ];
    }

    public function persist()
    {
        if(Hash::check($this -> validation_string, session() -> get('login_validation_string'))){
            session() -> forget('login_validation_string');
            session() -> forget('login_encrypted_message');
            return redirect()->route('profile.index');
        }
        else
            throw new RequestException("Your validation string is not correct!");
        return redirect() -> back();
    }
}
