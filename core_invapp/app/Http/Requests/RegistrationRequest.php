<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrationRequest extends FormRequest
{
    private $cbConfirm;

    public function __construct()
    {
        $this->cbConfirm = page_status('terms', true) ? 'required' : 'nullable';
    }

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
            'name' => 'required|string|max:190',
            'email' => 'required|email|regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,9}$/ix|max:190|unique:users',
            'password' => 'required|min:6',
            'confirmation' => $this->cbConfirm,
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'email.unique' => __("An account with the given email already exists."),
            'email.regex' => __("Please enter a valid email address.")
        ];
    }
}
