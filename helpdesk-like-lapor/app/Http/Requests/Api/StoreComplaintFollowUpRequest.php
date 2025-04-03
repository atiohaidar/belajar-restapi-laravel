<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreComplaintFollowUpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Check ComplaintFollowUpPolicy@create based on the Complaint.
     */
    public function authorize(): bool
    {
        $complaint = $this->route('complaint'); // Assumes route param {complaint}
        return $this->user()->can('create', [\App\Models\ComplaintFollowUp::class, $complaint]);
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'min:10', 'max:10000'],
             // Agency ID might be set automatically based on user or left null
             // 'agency_id' => ['nullable', 'uuid', 'exists:agencies,id'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }

     protected function failedAuthorization()
     {
          throw new HttpResponseException(response()->json([
              'message' => 'You do not have permission to add a follow-up to this complaint.'
          ], 403));
     }
}