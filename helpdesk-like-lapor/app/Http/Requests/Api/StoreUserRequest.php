<?php

namespace App\Http\Requests\Api;

use App\Models\User; // Import User model
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule; // Import Rule
use Illuminate\Validation\Rules\Password; // Import Password rule

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy handles authorization
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique(User::class)], // Unique rule for User model
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', Password::defaults()], // Use default strong password rules
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', 'string', Rule::in(['Admin', 'Agency Manager', 'Reporter'])],
            // Agency ID is required only if role is Agency Manager
            'agency_id' => [
                'nullable', // Allow null for Admin/Reporter
                'uuid',
                'exists:agencies,id',
                Rule::requiredIf($this->input('role') === 'Agency Manager'),
            ],
        ];
    }

     /**
      * Get custom messages for validator errors.
      *
      * @return array
      */
     public function messages(): array
     {
         return [
             'agency_id.required' => 'The agency field is required when role is Agency Manager.',
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