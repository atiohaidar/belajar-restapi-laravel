<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Use policy check for 'create' action
        return $this->user()->can('create', \App\Models\Complaint::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:65535'], // Max TEXT length roughly
            'category_id' => ['required', 'uuid', 'exists:complaint_categories,id'],
            'priority' => ['nullable', 'string', Rule::in(['Low', 'Medium', 'High'])],
            // Validation for attachments (array of files)
            'attachments' => ['nullable', 'array', 'max:5'], // Limit number of files
            'attachments.*' => [
                'file',
                // Adjust max size (in KB) and mime types as needed
                'max:5048', // Max 5MB per file
                'mimes:jpg,jpeg,png,pdf,doc,docx',
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