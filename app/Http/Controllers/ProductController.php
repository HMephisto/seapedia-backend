<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('store:id,store_name,address_detail')
            ->where('stock', '>', 0); 

        if ($request->has('search')) {
            $query->where('name', 'ILIKE', '%' . $request->search . '%');
        }

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('sort_price')) {
            $query->orderBy('price', $request->sort_price === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $products = $query->paginate(10);

        return response()->json($products);
    }

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

        if ($product->image_url) {
            Storage::disk('public')->delete($product->image_url);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully.']);
    }

    public function uploadImage(Request $request, $id)
    {
        $store = $this->getSellerStore($request);
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        if (!$store || $product->store_id !== $store->id) {
            return response()->json(['message' => 'You do not own this product.'], 403);
        }

        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        // If there is an existing stored image path, delete the old file
        if ($product->image_url) {
            Storage::disk('public')->delete($product->image_url);
        }

        $path = $request->file('image')->store('product-images', 'public');
        $product->update([
            'image_url' => $path,
        ]);

        return response()->json([
            'message' => 'Product image uploaded successfully.',
            'product' => $product->fresh(),
        ]);
    }

    protected function getSellerStore(Request $request)
    {
        return Store::where('seller_id', $request->user()->id)->first();
    }
}
