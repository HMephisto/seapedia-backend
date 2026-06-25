<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();

        if (Store::where('seller_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'You already have a store.',
            ], 409);
        }

        $validated = $request->validate([
            'store_name' => 'required|string|max:100|unique:stores,store_name',
            'description' => 'nullable|string',
            'address_detail' => 'nullable|string',
        ]);

        $store = Store::create([
            'seller_id' => $user->id,
            'store_name' => $validated['store_name'],
            'description' => $validated['description'] ?? null,
            'address_detail' => $validated['address_detail'] ?? null,
        ]);

        return response()->json([
            'message' => 'Store created successfully.',
            'store' => $store,
        ], 201);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $store = Store::where('seller_id', $user->id)->first();

        if (!$store) {
            return response()->json([
                'message' => 'You do not have a store yet.',
            ], 404);
        }

        $validated = $request->validate([
            'store_name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('stores', 'store_name')->ignore($store->id),
            ],
            'description' => 'nullable|string',
            'address_detail' => 'nullable|string',
        ]);

        $store->update($validated);

        return response()->json([
            'message' => 'Store updated successfully.',
            'store' => $store->fresh(),
        ]);
    }

    public function show($id)
    {
        $store = Store::where('id', $id)
            ->with([
                'products' => function ($q) {
                    $q->where('stock', '>', 0)
                        ->orderBy('created_at', 'desc');
                }
            ])
            ->first();

        if (!$store) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        return response()->json(['store' => $store]);
    }

    public function hasStore(Request $request)
    {
        return response()->json([
            'has_store' => Store::where('seller_id', $request->user()->id)->exists(),
        ]);

    }

    public function myStore(Request $request)
    {
        $store = Store::where('seller_id', $request->user()->id)->first();

        if (!$store) {
            return response()->json([
                'message' => 'You do not have a store yet.'
            ], 404);
        }

        return response()->json([
            'store' => $store
        ]);
    }
}
