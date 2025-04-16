<?php

namespace Modules\Faq\app\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Faq\app\Models\Faq;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;

class FaqController extends Controller
{
    /**
     * Display a listing of FAQs.
     * 
     * @return Response
     */
    public function index(Request $request)
    {
        $query = Faq::query();
        
        $isAdminOrManager = false;
        
        if (Auth::check()) {
            // Load user with roles to avoid N+1 queries
            $user = Auth::user()->load('roles');

            $roles = $user->roles->pluck('name')->toArray();
            $isAdminOrManager = in_array('admin', $roles) || in_array('manager', $roles);
        }
        
        // For public access or regular users, only show published FAQs
        // Admin and managers should see all FAQs (both published and unpublished)
        if (!$isAdminOrManager) {
            $query->where('is_published', true);
        }
        
        // Filter by category if provided
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        
        $faqs = $query->orderBy('order', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $faqs
        ]);
    }
    public function search(Request $request)
    {
        $query = Faq::query();
        
        // For public access or regular users, only show published FAQs
        // Admin and managers should see all FAQs (both published and unpublished)
        if (!Auth::check() || (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('manager'))) {
            $query->where('is_published', true);
        }
        
        // Filter by category if provided
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        
        // Search by question or answer
        if ($request->has('q')) {
            $query->where(function($q) use ($request) {
                $q->where('question', 'like', '%' . $request->q . '%')
                  ->orWhere('answer', 'like', '%' . $request->q . '%');
            });
        }
        
        $faqs = $query->orderBy('order', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $faqs
        ]);
    }

    /**
     * Store a newly created FAQ.
     * 
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        // Authorize the action
        Gate::authorize('create', Faq::class);

        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'is_published' => 'boolean',
            'order' => 'integer|min:0',
            'category' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $faq = Faq::create([
            'question' => $request->question,
            'answer' => $request->answer,
            'is_published' => $request->is_published ?? true,
            'order' => $request->order ?? 0,
            'category' => $request->category,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'FAQ created successfully',
            'data' => $faq
        ], 201);
    }

    /**
     * Display the specified FAQ.
     * 
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $faq = Faq::find($id);

        if (!$faq) {
            return response()->json([
                'status' => 'error',
                'message' => 'FAQ not found'
            ], 404);
        }

        // Authorize viewing this FAQ (public or admin/manager only)
        Gate::authorize('view', $faq);

        return response()->json([
            'status' => 'success',
            'data' => $faq
        ]);
    }

    /**
     * Update the specified FAQ.
     * 
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $faq = Faq::find($id);

        if (!$faq) {
            return response()->json([
                'status' => 'error',
                'message' => 'FAQ not found'
            ], 404);
        }

        // Authorize the action
        Gate::authorize('update', $faq);

        $validator = Validator::make($request->all(), [
            'question' => 'string|max:255',
            'answer' => 'string',
            'is_published' => 'boolean',
            'order' => 'integer|min:0',
            'category' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $faq->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'FAQ updated successfully',
            'data' => $faq
        ]);
    }

    /**
     * Remove the specified FAQ.
     * 
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $faq = Faq::find($id);

        if (!$faq) {
            return response()->json([
                'status' => 'error',
                'message' => 'FAQ not found'
            ], 404);
        }

        // Authorize the action
        Gate::authorize('delete', $faq);

        $faq->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'FAQ deleted successfully'
        ]);
    }
    
    /**
     * Get FAQ categories.
     * 
     * @return Response
     */
    public function categories()
    {
        // Authorize the action for viewing categories
        Gate::authorize('viewCategories', Faq::class);
        
        $query = Faq::query();
        
        // For public access, only include categories from published FAQs
        if (!Auth::check() || (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('manager'))) {
            $query->where('is_published', true);
        }
        
        $categories = $query->select('category')
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');
            
        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }
}
