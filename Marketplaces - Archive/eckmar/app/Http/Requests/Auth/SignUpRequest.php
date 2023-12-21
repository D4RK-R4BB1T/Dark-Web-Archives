<?php

namespace App\Http\Requests\Auth;

use App\Marketplace\Encryption\Cipher;
use App\Marketplace\Encryption\Keypair;
use App\Marketplace\Utility\Mnemonic;
use App\Rules\Captcha;
use App\User;
use Illuminate\Foundation\Http\FormRequest;
use Defuse\Crypto\Crypto;

class SignUpRequest extends FormRequest {

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'captcha' => ['required', new Captcha()],
            'username' => 'required|unique:users|alpha_num|min:4|max:12',
            'password' => 'required|confirmed|min:8',

        ];
    }

    /**
     * Get messages for validation rules
     *
     * @return array
     */
    public function messages() {
        return [
            'captcha.required' => 'Captcha is required',
            'username.required' => 'Username is required',
            'username.min' => 'Username must have at least 4 characters',
            'username.unique' => 'Account with that username already exists',
            'username.max' => 'Username cannot be longer than 12 characters',
            'username.alpha_num' => 'You can only use alpha-numeric characters for username',
            'password.required' => 'Password is required',
            'password.min' => 'Password must have at least 8 characters',
            'password.confirmed' => 'Password must be confirmed',
            'password.different' => 'Password can\'t be same as username',
        ];
    }

    /**
     * Try to generate keys for user and complete registration
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \ParagonIE\EasyRSA\Exception\InvalidKeyException
     */
    public function persist() {

        //check if there is referral id
        if ($this->refid !== null) {
            $referred_by = User::where('referral_code', $this->refid)->first();
        }
        else
            $referred_by = null;


        // create users public and private RSA Keys
        $keyPair = new Keypair();
        $privateKey = $keyPair->getPrivateKey();
        $publicKey =   $keyPair->getPublicKey();
        // encrypt private key with user's password
        $encryptedPrivateKey = Crypto::encryptWithPassword($privateKey, $this->password);

        $mnemonic = (new Mnemonic())->generate(config('marketplace.mnemonic_length'));

        $user = new User();
        $user->username = $this->username;
        $user->password = bcrypt($this->password);
        $user->mnemonic = bcrypt(hash('sha256', $mnemonic));
        $user->referral_code = strtoupper(str_random(6));
        $user->msg_public_key = encrypt($publicKey);
        $user->msg_private_key = $encryptedPrivateKey;
        $user -> referred_by = optional($referred_by) -> id;
        $user->save();

        // generate vendor addresses
        $user->generateDepositAddresses();

        session()->flash('mnemonic_key', $mnemonic);
    }
}
