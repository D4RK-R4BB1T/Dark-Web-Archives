<?php

namespace App\Http\Requests\Auth;

use App\Exceptions\RequestException;
use App\Marketplace\Encryption\Keypair;
use Defuse\Crypto\Crypto;
use Illuminate\Foundation\Http\FormRequest;
use App\User;
use Hash;

class RecoverPasswordMnemonicRequest extends FormRequest
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
            'username' => 'required|exists:users,username',
            'mnemonic'=> 'required',
            'password' => 'required|string|confirmed|min:8'
        ];
    }

    public function messages(){
        return[
            'username.required' => 'Username is required',
            'username.exists' => 'User with that username does not exist',
            'mnemonic.required'=>'Mnemonic is required',
            'password.required'=>'Password is required',
            'password.confirmed' => 'You didn\'t confirm password correctly!',
            'password.min' => 'Password must have at least 8 characters',
        ];
    }

    public function persist(){
        $user = User::where('username', $this->username)->first();
        //check if user exist
        if ($user == null) {
            throw new RequestException('Could not find user with that username');
        }
        //check if mnemonics match
        if (
        !Hash::check(hash('sha256', $this->mnemonic), $user->mnemonic)
        ) {
            throw new RequestException('Mnemonic is not valid');
        }

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
