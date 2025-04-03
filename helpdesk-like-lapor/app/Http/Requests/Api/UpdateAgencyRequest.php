<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateAgencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy handles authorization
    }

    public function rules(): array
    {
        $agencyId = $this->route('agency')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('agencies', 'email')->ignore($agencyId),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'parent_id' => [
                'nullable', // Allow setting parent to null
                'uuid',
                'exists:agencies,id',
                // Prevent setting parent_id to self
                Rule::notIn([$agencyId]),
            ],
        ];
    }

     protected function failedValidation(Validator $validator): void
     {
         throw new HttpResponseException(response()->json([
             'message' => 'The given data was invalid.',
             'errors' => $validator->errors(),
         ], 422));
     }
}