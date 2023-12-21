<?php

namespace App\Http\Requests\PGP;

use App\Exceptions\RequestException;
use App\Exceptions\ValidationException;
use const App\Marketplace\NEW_PGP_ENCRYPTED_MESSAGE;
use const App\Marketplace\NEW_PGP_SESSION_KEY;
use const App\Marketplace\NEW_PGP_VALIDATION_NUMBER_KEY;
use App\Marketplace\PGP;
use Illuminate\Foundation\Http\FormRequest;

class NewPGPKeyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth() -> check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'newpgp' => 'string'
        ];
    }

    public function messages()
    {
        return [
            'newpgp.string' => 'You must enter your new PGP key!'
        ];
    }

    public function persist()
    {
        $newUsersPGP = $this -> newpgp;
        $validationNumber = rand(100000000000, 999999999999); // Radnom number to confirm
        $decryptedMessage = "You have successfully decrypted this message.\nTo validate this key please copy validation number to the field on the site\nValidation number:". $validationNumber;
        // Encrypt throws \Exception
        try{
            $encryptedMessage = PGP::EncryptMessage($decryptedMessage, $newUsersPGP);
        }
        catch (\Exception $e){
            throw new RequestException($e -> getMessage());
        }

        // store data to sessions

        session() -> put(PGP::NEW_PGP_VALIDATION_NUMBER_KEY, $validationNumber );
        session() -> put(PGP::NEW_PGP_SESSION_KEY, $newUsersPGP);
        session() -> put(PGP::NEW_PGP_ENCRYPTED_MESSAGE, $encryptedMessage);

    }
}
