<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
class RegisterRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
             'user_type' => 'required', 
             'name' => 'required', 
             'gender' => 'in:1,2',
           //  'email' => 'required|email:rfc,dns,filter|unique:users,email,NULL,id,deleted_at,NULL', 
             //'phone_number' => 'required|unique:users', 
             'country_code' => 'required',
             'password' => 'required|min:6|same:confirm_password',
             'confirm_password' => 'min:6',
        ];
    }

     public function failedValidation(Validator $validator)
    {
        // throw new HttpResponseException(response()->json([
        //     'success'   => false,
        //     'message'   => $validator->errors()->first(),
        // ], 400));
        throw new HttpResponseException(
          returnValidationErrorResponse($validator->errors()->first())
        );
    }
}
