<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductSearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'q' => ['required', 'string'],
        ]);

        $products = Product::query()
            ->where(function ($query) use ($validated) {
                $like = '%' . $validated['q'] . '%';
                $collation = 'utf8mb4_unicode_ci';

                $query->whereRaw("name COLLATE {$collation} LIKE ?", [$like])
                    ->orWhereRaw("COALESCE(description, '') COLLATE {$collation} LIKE ?", [$like]);
            })
            ->get();

        return response()->json($products);
    }
}
