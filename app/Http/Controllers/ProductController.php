<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // Product list with optional search and filter
    public function index(Request $request)
    {
        $query = Product::with('store:id,store_name,address_detail')
            ->where('stock', '>', 0); // only show in stock products

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by store
        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // Sort by price
        if ($request->has('sort_price')) {
            $query->orderBy('price', $request->sort_price === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $products = $query->paginate(10);

        return response()->json($products);
    }

    // Product detail
    public function show($id)
    {
        $product = Product::with([
            'store:id,seller_id,store_name,address_detail,description',
            'store.seller:id,full_name',
        ])->find($id);

        if (! $product) {
            return response()->json([
                'message' => 'Product not found',
            ], 404);
        }

        return response()->json($product);
    }
}
