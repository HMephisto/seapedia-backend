<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
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

    

    public function store(Request $request)
    {
        $store = $this->getSellerStore($request);

        if (!$store) {
            return response()->json(['message' => 'You do not have a store yet.'], 404);
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'image_url'   => 'nullable|url|max:255',
        ]);

        $product = Product::create([
            'store_id'    => $store->id,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price'       => $validated['price'],
            'stock'       => $validated['stock'],
            'image_url'   => $validated['image_url'] ?? null,
        ]);

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => $product,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $store   = $this->getSellerStore($request);
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        if (!$store || $product->store_id !== $store->id) {
            return response()->json(['message' => 'You do not own this product.'], 403);
        }

        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:100',
            'description' => 'nullable|string',
            'price'       => 'sometimes|required|numeric|min:0',
            'stock'       => 'sometimes|required|integer|min:0',
            'image_url'   => 'nullable|url|max:255',
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => $product->fresh(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $store   = $this->getSellerStore($request);
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        if (!$store || $product->store_id !== $store->id) {
            return response()->json(['message' => 'You do not own this product.'], 403);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully.']);
    }
}
