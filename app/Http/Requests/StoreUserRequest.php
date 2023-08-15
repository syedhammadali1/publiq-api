<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'firstname' => 'required|regex:/^[a-z]+$/i',
            'lastname' => 'required|regex:/^[a-z]+$/i',
            'email' => 'required|email|unique:users,email|regex:/^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/g',
            'username' => 'required|unique:users,name|regex:/(^([a-zA-z]+)(\d+)?$)/u',
            'password' => 'required|string|min:8|confirmed',
            'country' => 'required',
            // 'age' => 'required|max:2',
            'dob' => 'required|date',
            'gender' => 'required',
            // 'type' => 'required',
            'terms' => 'required',
            'question_2' => 'required',
            'g-recaptcha-response' => 'required|captcha',
            'additional_comments' => 'nullable|min:10|max:250',
        ];
    }

    public function messages()
    {
        return [
            'firstname.required' => 'First name is required.',
            'lastname.required' => 'Last name is required.',
            'email.required' => 'Email is required.',
            'username.required' => 'Username is required.',
            'password.required' => 'Password is required.',
            'country.required' => 'Country is required.',
            'age.required' => 'Age is required.',
            'terms.required' => 'Terms & Condition is required.',
            'age.max' => 'The age must not be greater than 2 digits.',
            'dob.required' => 'Date of birth is required.',
            'gender.required' => 'Gender is required.',
            // 'type.required' => 'Type is required.',
            'question_2.required' => 'This field is required.',
            'g-recaptcha-response.required' => 'Please confirm you are not a bot',
        ];
    }
}
