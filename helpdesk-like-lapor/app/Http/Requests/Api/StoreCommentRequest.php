<?php

namespace App\Http\Requests\Api;

use App\Models\Complaint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Check ComplaintPolicy@addComment.
     */
    public function authorize(): bool
    {
        // Get the complaint instance from the route
        // Note: Route parameter name might be 'complaint' or 'comment' depending on route definition, adjust if needed.
        // Assuming route is /complaints/{complaint}/comments, parameter is 'complaint'
        $complaint = $this->route('complaint');
        return $this->user()->can('addComment', $complaint);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:3', 'max:5000'], // Reasonable limits
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }

    /**
     * Handle a failed authorization attempt.
     * Override to return JSON 403 response.
     *
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function failedAuthorization()
    {
         throw new HttpResponseException(response()->json([
             'message' => 'You do not have permission to comment on this complaint.'
         ], 403));
    }
}