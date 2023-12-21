<?php

namespace App\Http\Requests\Profile;

use App\Exceptions\RequestException;
use App\Marketplace\Encryption\Cipher;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class DecryptMessagesRequest extends FormRequest
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
            'password' => 'required'
        ];
    }

    public function messages() {
        return [
            'password.required' => 'Password is required'
        ];
    }

    public function persist() {
        $user = auth()->user();
        throw_unless(Hash::check($this->password,$user->password),new RequestException('Password does not match'));
        if (session()->has('private_rsa_key_decrypted'))
            session()->forget('private_rsa_key_decrypted');

        try{
            $key = Crypto::decryptWithPassword($user->msg_private_key,$this->password);
            session()->put('private_rsa_key_decrypted',encrypt($key));
        } catch (WrongKeyOrModifiedCiphertextException | EnvironmentIsBrokenException | \TypeError $e){
            throw new RequestException('Error occurred, we were unable to decrypt your messages');
        }
    }
}
