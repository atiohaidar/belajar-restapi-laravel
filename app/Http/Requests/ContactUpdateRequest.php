<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() != null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "firstname" => ["required","max:100", "string"],
            "lastname" => ["nullable","max:100", "string"],
            "email" => ["nullable","max:100", "string"],
            "phone" => ["nullable","max:100", "string"],
        ];
    }
    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator){
        throw new \Illuminate\Http\Exceptions\HttpResponseException(response([
            "errors" => $validator->getMessageBag(),
        ], 400));
    }

}
