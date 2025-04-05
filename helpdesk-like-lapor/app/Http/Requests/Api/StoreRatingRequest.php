<?php

namespace App\Http\Requests\Api;

use App\Models\Complaint; // Import Complaint
use App\Policies\RatingPolicy; // Import Policy if checking here
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreRatingRequest extends FormRequest
{
    // Property to hold the resolved complaint instance
    protected ?Complaint $complaintInstance = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // 1. Basic role check (must be Reporter)
        if (!$this->user() || $this->user()->role !== 'Reporter') {
            return false;
        }
        return true; // algoritma di bawah di skip, jadi disini untuk otorisainya kebanyakan lewat policy aja
        
        // 2. Check if rating is linked to a valid complaint ID
        if (!$this->input('complaint_id')) {
            // Allow general rating if needed? Or always require complaint_id?
            // For now, let's require complaint_id for rating.
            return false;
        }
        
        // 3. Find the complaint instance
        $this->complaintInstance = Complaint::query()->where('id', $this->input('complaint_id'))->first();
        if (!$this->complaintInstance) {
            // Complaint not found - validation will catch exists rule, but good to check here too
            return false; // Or maybe allow rating even if complaint deleted? Policy logic decides.
        }
        
        // 4. Use the specific policy method: can the user rate THIS complaint?
        return $this->user()->can('rateComplaint', $this->complaintInstance);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Complaint ID is required and must exist (checked partly in authorize)
            'complaint_id' => ['required', 'uuid', 'exists:complaints,id'],
            // Agency ID should be derived from complaint, not sent by user
            // 'agency_id' => ['required', 'uuid', 'exists:agencies,id'],
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:5000'],
        ];
    }

     /**
      * Get the validated data from the request.
      * Add the derived agency_id.
      *
      * @param  array|int|string|null  $key
      * @param  mixed  $default
      * @return mixed
      */
     public function validated($key = null, $default = null)
     {
        // Ensure complaint instance is loaded (might be called multiple times)
        if (!$this->complaintInstance && $this->input('complaint_id')) {
             $this->complaintInstance = Complaint::query()->where('id', $this->input('complaint_id'))->first();
        }

         $validated = parent::validated($key, $default);

         // Add the agency_id from the related complaint
         if ($this->complaintInstance) {
             $validated['agency_id'] = $this->complaintInstance->agency_id;
         } else {
             // Handle case where complaint might not be found (though validation should prevent)
             // Or if allowing rating without complaint_id later
             $validated['agency_id'] = null; // Or throw error
         }


         return $validated;
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
              'message' => 'You do not have permission to rate this complaint or have already rated it. e'
          ], 403));
     }
}