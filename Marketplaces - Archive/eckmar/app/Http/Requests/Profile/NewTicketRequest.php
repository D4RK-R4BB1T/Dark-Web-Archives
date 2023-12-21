<?php

namespace App\Http\Requests\Profile;

use App\Conversation;
use App\Events\Message\MessageSent;
use App\Message;
use Illuminate\Foundation\Http\FormRequest;

class NewTicketRequest extends FormRequest
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
            'title' => 'required|string',
            'message' => 'required|string'
        ];
    }

}
