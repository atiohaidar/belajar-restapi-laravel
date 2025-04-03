<?php

namespace App\Http\Requests\Api;

use App\Models\Complaint; // Import Complaint
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
         // Get the complaint instance from the route
         $complaint = $this->route('complaint');
         // Use policy check for 'update' action
         return $this->user()->can('update', $complaint);
    }

    public function rules(): array
    {
        $user = $this->user();
        $complaint = $this->route('complaint'); // Get complaint from route binding

        $rules = [];

        // Only Admin or relevant Manager can update these fields
        if ($user->can('update', $complaint)) { // Re-check policy or role for safety
            $rules['status'] = ['sometimes', 'required', 'string', Rule::in(['Pending', 'In Progress', 'Resolved', 'Archived'])]; // Exclude 'Unprocessed'?
            $rules['priority'] = ['sometimes', 'required', 'string', Rule::in(['Low', 'Medium', 'High'])];
             // Allow assigning/changing agency (only Admin? or Manager within hierarchy?)
             // For simplicity: Admin can assign any, Manager cannot re-assign via this general update?
             if ($user->role === 'Admin') {
                 $rules['agency_id'] = ['nullable', 'uuid', 'exists:agencies,id'];
             }
        }

        // Maybe allow reporter to update title/description IF status is still 'Unprocessed'? (More complex)
        // if ($user->id === $complaint->user_id && $complaint->status === 'Unprocessed') {
        //     $rules['title'] = ['sometimes', 'required', 'string', 'max:255'];
        //     $rules['description'] = ['sometimes', 'required', 'string', 'max:65535'];
        // }

        // Prevent updating fields not allowed
        if (empty($rules)) {
             // If no rules applicable based on role/status, add a dummy rule to prevent empty rules array
             // Or handle this in the controller/policy by denying the request earlier.
             // Let's assume the authorize() check is sufficient.
        }

        return $rules;
    }

    /**
     * Prepare the data for validation.
     * Ensure agency_id is explicitly null if passed as empty string.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('agency_id') && $this->input('agency_id') === '') {
            $this->merge([
                'agency_id' => null,
            ]);
        }
    }


    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}