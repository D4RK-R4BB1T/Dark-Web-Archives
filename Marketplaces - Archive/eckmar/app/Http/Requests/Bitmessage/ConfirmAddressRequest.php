<?php

namespace App\Http\Requests\Bitmessage;

use App\Exceptions\RequestException;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmAddressRequest extends FormRequest
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
            'code' => 'required'
        ];
    }
    public function messages(){
        return [
            'code.required' => 'Confirmation code is required'
        ];
    }
    public function persist(){
        if (!session()->has('bitmessage_confirmation')){
            throw new RequestException('Something went wrong, try again later');
        }
        $data = session()->get('bitmessage_confirmation');
        $time = Carbon::parse($data['time']);
        $validTime = config('bitmessage.confirmation_valid_time');
        if ($time->diffInMinutes(Carbon::now()) > $validTime){
            session()->forget('bitmessage_confirmation');
            throw new RequestException('Code we sent is no longer valid, send new confirmation code');
        }
        if ($this->code !== $data['code']){
            throw new RequestException('Code is not valid');
        }
        $user = auth()->user();
        $user->bitmessage_address = $data['address'];
        $user->save();
        session()->flash('success','Address confirmed successfully');
        session()->forget('bitmessage_confirmation');
    }
}
