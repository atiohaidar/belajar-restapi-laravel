<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator; // Import Validator
use Illuminate\Http\Exceptions\HttpResponseException; // Import HttpResponseException

class StoreComplaintCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Authorization is handled by Policy, so return true here.
     */
    public function authorize(): bool
    {
        // Alternatively, you can duplicate policy check here, but Policy is cleaner
        // return $this->user()->role === 'Admin';
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
            'name' => ['required', 'string', 'max:255', 'unique:complaint_categories,name'],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * Handle a failed validation attempt.
     * Override to return JSON response for API.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}