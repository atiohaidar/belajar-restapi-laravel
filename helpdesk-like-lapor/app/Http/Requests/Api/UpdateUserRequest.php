<?php

namespace App\Http\Requests\Api;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy handles authorization
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id; // Get user model from route binding

        return [
             'name' => ['sometimes', 'required', 'string', 'max:255'],
             'username' => [
                 'sometimes',
                 'required',
                 'string',
                 'max:255',
                 Rule::unique(User::class)->ignore($userId)
             ],
             'email' => [
                 'sometimes',
                 'required',
                 'string',
                 'email',
                 'max:255',
                 Rule::unique(User::class)->ignore($userId)
             ],
             // Password update is optional
             'password' => ['sometimes', 'nullable', 'confirmed', Password::defaults()],
             'phone' => ['nullable', 'string', 'max:50'],
             'role' => ['sometimes', 'required', 'string', Rule::in(['Admin', 'Agency Manager', 'Reporter'])],
             // Agency ID: Allow null, required if role is Agency Manager
             'agency_id' => [
                 'nullable',
                 'uuid',
                 'exists:agencies,id',
                 // Required if role IS being set/updated to Agency Manager
                 Rule::requiredIf(function () {
                     // Check the incoming role value OR the existing role if not provided
                     $role = $this->input('role', $this->route('user')?->role);
                     return $role === 'Agency Manager';
                 }),
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