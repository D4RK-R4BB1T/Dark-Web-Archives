<?php

namespace App\Http\Requests\Bitmessage;

use App\Exceptions\RequestException;
use App\Marketplace\Bitmessage\Bitmessage;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class SendConfirmationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'address' => 'required'
        ];
    }
    public function messages(){
        return [
            'address.required' => 'Valid Bitmessage address is required'
        ];
    }

    /**
     * Requires bitmessage instance and send confirmation message to user
     *
     * @param Bitmessage $bitmessage
     */
    public function persist(Bitmessage $bitmessage,$marketAddress){
        if (session()->has('bitmessage_confirmation')){

            $time = session()->get('bitmessage_confirmation')['time'];
            $validTime = config('bitmessage.confirmation_msg_frequency');

            if ($time->diffInSeconds(Carbon::now()) < $validTime){
                throw new RequestException("You can request new code every {$validTime} ".str_plural('second',$validTime));
                return;
            }
        }

        $confirmationCode = strtoupper(str_random(8));
        $subject = config('app.name').' Bitmessage Address Verification #'.strtoupper(str_random(5));
        $validTime = config('bitmessage.confirmation_valid_time');
        $minute = str_plural('minute',$validTime);
        $message = "Confirmation code: {$confirmationCode} . Code will be valid for {$validTime} {$minute}";
        try{
            $bitmessage->sendMessage($this->address,$marketAddress,$subject,$message);
            session()->flash('success','Confirmation code sent');
            if (session()->has('bitmessage_confirmation')){
                session()->forget('bitmessage_confirmation');
            }
            $data = [
                'address' => $this->address,
                'code' => $confirmationCode,
                'time' => Carbon::now()
            ];
            session()->put('bitmessage_confirmation',$data);
        } catch (\Exception $e){
            throw new RequestException('Could not send message to the provided address');
        }
    }
}
