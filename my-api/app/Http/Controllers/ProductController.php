<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{

    public function index()
    {
        return response()->json([
            'products' => [
                ['id' => 1, 'name' => 'Product 1', 'price' => 100],
                ['id' => 2, 'name' => 'Product 2', 'price' => 200],
            ]
        ]);
    }    
    public function store(Request $request)
{
    $data = $request->all();
    // Process and validate the data
}

    public function show($id)
    {
        return response()->json([
            'product' => ['id' => $id, 'name' => 'Product ' . $id, 'price' => 100 * $id]
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();
        // Process and validate the data
    }

    public function destroy($id)
    {
        // Delete the product
    }
    public function search(Request $request)
    {
        $query = $request->input('query');
        // Perform search logic
        return response()->json([
            'products' => [
                ['id' => 1, 'name' => 'Product 1', 'price' => 100],
                ['id' => 2, 'name' => 'Product 2', 'price' => 200],
            ]
        ]);
    }
    public function filter(Request $request)
    {
        $filters = $request->all();
        // Perform filtering logic
        return response()->json([
            'products' => [
                ['id' => 1, 'name' => 'Product 1', 'price' => 100],
                ['id' => 2, 'name' => 'Product 2', 'price' => 200],
            ]
        ]);
    }
    public function sort(Request $request)
    {
        $sortBy = $request->input('sort_by');
        $sortOrder = $request->input('sort_order', 'asc');
        // Perform sorting logic
        return response()->json([
            'products' => [
                ['id' => 1, 'name' => 'Product 1', 'price' => 100],
                ['id' => 2, 'name' => 'Product 2', 'price' => 200],
            ]
        ]);
    }

}
