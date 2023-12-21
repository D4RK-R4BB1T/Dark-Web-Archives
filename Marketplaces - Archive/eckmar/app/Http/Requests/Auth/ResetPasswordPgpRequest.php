<?php

namespace App\Http\Requests\Auth;

use App\Marketplace\Encryption\Keypair;
use Defuse\Crypto\Crypto;
use Illuminate\Foundation\Http\FormRequest;
use App\Marketplace\PGP;
use const App\Marketplace\NEW_PGP_VALIDATION_NUMBER_KEY;
use App\User;

class ResetPasswordPgpRequest extends FormRequest
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
            'validation_string' => 'required|numeric',
            'password' => 'required|string|confirmed|min:8'
        ];
    }

    public function messages(){
        return[
            'validation_string.required'=> 'Validation number is required',
            'password.required'=>'Password is required',
            'password.confirmed' => 'You didn\'t confirm password correctly!',
            'password.min' => 'Password must have at least 8 characters',
        ];
    }

    public function persist(){
        $correctValidationNumber = session() -> get(PGP::NEW_PGP_VALIDATION_NUMBER_KEY);
        if($correctValidationNumber == $this -> validation_string){
            $user=User::where('username', session()->get('username'))->first();
            $user->password = bcrypt($this -> password);

            // generate new key pair
            $keyPair = new Keypair();
            $privateKey = $keyPair->getPrivateKey();
            $publicKey =   $keyPair->getPublicKey();
            // encrypt private key with user's password
            $encryptedPrivateKey = Crypto::encryptWithPassword($privateKey, $this->password);
            $user->msg_public_key = encrypt($publicKey);
            $user->msg_private_key = $encryptedPrivateKey;

            $user->save();
            session() -> flash('success', 'You have successfully changed your password!');
            return redirect() -> route('auth.signin');
        }
    }
}
