<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressCreateRequest extends FormRequest
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
            
            "street"=> ["nullable","string","max:200"],
            "city"=> ["nullable","string","max:200"],
            "province"=> ["nullable","string","max:200"], 
            "country"=> ["required","string","max:200"],
            "postal_code"=> ["nullable","string","max:200"]
        ];
    }
    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator){
        throw new \Illuminate\Http\Exceptions\HttpResponseException(response([
            "errors" => $validator->getMessageBag(),
        ], 400));
    }
}
