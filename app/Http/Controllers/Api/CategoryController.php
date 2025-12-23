<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::all();

        return response()->json([
            'message' => 'Daftar kategori.',
            'data' => [
                'categories' => [
                    'items' => $categories,
                    'total' => $categories->count(),
                ],
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = Category::findOrFail($id);
        $products = $category->products()->get();

        return response()->json([
            'message' => 'Produk berdasarkan kategori.',
            'data' => [
                'category' => $category,
                'products' => $products,
            ],
        ]);
    }
}
