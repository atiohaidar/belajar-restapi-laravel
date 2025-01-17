<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "username" => ["required","max:100", "string"],
            "password" => ["required","max:100","string"],
            "name" => ["required","max:100","string"]
        ];
    }
    //  ini kalau input daataa nya error bakal masuk ke sini
    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator){
        throw new \Illuminate\Http\Exceptions\HttpResponseException(response([
            "errors" => $validator->getMessageBag(),
        ], 400));
    }
}
