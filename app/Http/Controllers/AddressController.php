<?php

namespace App\Http\Controllers;

use App\Models\Address;
use DB;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        $addresses = Address::where('buyer_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();
 
        return response()->json([
            'success' => true,
            'data' => $addresses,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipient_name' => 'required|string|max:100',
            'phone'          => 'required|string|max:20',
            'address_detail' => 'required|string',
            'is_default'     => 'sometimes|boolean',
        ]);
 
        $buyerId = $request->user()->id;
        $isDefault = $validated['is_default'] ?? false;
 
        $hasAny = Address::where('buyer_id', $buyerId)->exists();
        if (!$hasAny) {
            $isDefault = true;
        }
 
        DB::transaction(function () use (&$address, $buyerId, $validated, $isDefault) {
            if ($isDefault) {
                Address::where('buyer_id', $buyerId)->update(['is_default' => false]);
            }
 
            $address = Address::create([
                'buyer_id'       => $buyerId,
                'recipient_name' => $validated['recipient_name'],
                'phone'          => $validated['phone'],
                'address_detail' => $validated['address_detail'],
                'is_default'     => $isDefault,
            ]);
        });
 
        return response()->json([
            'success' => true,
            'message' => 'Address created successfully.',
            'data'    => $address,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $address = Address::where('id', $id)
            ->where('buyer_id', $request->user()->id)
            ->firstOrFail();
 
        $validated = $request->validate([
            'recipient_name' => 'sometimes|required|string|max:100',
            'phone'          => 'sometimes|required|string|max:20',
            'address_detail' => 'sometimes|required|string',
            'is_default'     => 'sometimes|boolean',
        ]);
 
        DB::transaction(function () use ($address, $validated, $request) {
            if (isset($validated['is_default']) && $validated['is_default']) {
                Address::where('buyer_id', $request->user()->id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }
 
            $address->update($validated);
        });
 
        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully.',
            'data'    => $address->fresh(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $address = Address::where('id', $id)
            ->where('buyer_id', $request->user()->id)
            ->firstOrFail();
 
        DB::transaction(function () use ($address, $request) {
            $wasDefault = $address->is_default;
            $address->delete();
 
            if ($wasDefault) {
                $next = Address::where('buyer_id', $request->user()->id)
                    ->orderBy('id')
                    ->first();
 
                if ($next) {
                    $next->update(['is_default' => true]);
                }
            }
        });
 
        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully.',
        ]);
    }

    public function setDefault(Request $request, $id)
    {
        $address = Address::where('id', $id)
            ->where('buyer_id', $request->user()->id)
            ->firstOrFail();
 
        DB::transaction(function () use ($address, $request) {
            Address::where('buyer_id', $request->user()->id)
                ->update(['is_default' => false]);
 
            $address->update(['is_default' => true]);
        });
 
        return response()->json([
            'success' => true,
            'message' => 'Default address updated.',
            'data'    => $address->fresh(),
        ]);
    }
}
