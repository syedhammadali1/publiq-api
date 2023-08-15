<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
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
            'first_name' => 'required',
            'last_name' => 'required',
            'gender' => 'required',
            'birthday' => 'required|date',
            'phone_no' => 'required',
            'about_me' => 'required'


        ];
    }

    public function messages()
    {
        return[
            'first_name.required' => 'First name is required ',
            'last_name.required' => 'Last name is required ',
            'gender.required' => 'Gender is required ',
            'birthday.required' => 'Birthday is required ',
            'phone_no.required' => 'Phone no is required ',
            'about_me.required' => 'About me is required ',

        ];
    }
}
