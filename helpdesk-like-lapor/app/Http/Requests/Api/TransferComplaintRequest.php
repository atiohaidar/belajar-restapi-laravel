<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule; // Import Rule

class TransferComplaintRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Use ComplaintTransferPolicy@create based on the Complaint.
     */
    public function authorize(): bool
    {
        $complaint = $this->route('complaint'); // Assumes route param {complaint}
        return $this->user()->can('create', [\App\Models\ComplaintTransfer::class, $complaint]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $complaint = $this->route('complaint');

        return [
            'to_agency_id' => [
                'required',
                'uuid',
                'exists:agencies,id',
                // Cannot transfer to the currently assigned agency
                Rule::notIn([$complaint->agency_id]),
            ],
            'reason' => ['nullable', 'string', 'max:1000'],
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
             'to_agency_id.not_in' => 'Cannot transfer complaint to the agency it is already assigned to.',
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
              'message' => 'You do not have permission to transfer this complaint.'
          ], 403));
     }
}