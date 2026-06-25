<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $buyer = $request->user();

        $product = Product::findOrFail($validated['product_id']);

        $cart = Cart::firstOrCreate(['buyer_id' => $buyer->id], ['store_id' => $product->store_id]);

        if ($cart->store_id !== $product->store_id) {
            return response()->json([
                'message' => 'Your cart already contains products from another store.'
            ], 409);
        }

        $item = CartItem::where('cart_id', $cart->id)->where('product_id', $product->id)->first();
        if ($item) {
            $item->quantity += $validated['quantity'];
            $item->save();
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $validated['quantity']
            ]);
        }

        return response()->json([
            'message' => 'Product added to cart.'
        ]);
    }

    public function updateQuantity(Request $request,$itemId) {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $item = CartItem::find($itemId);

        if (!$item) {
            return response()->json([
                'message' => 'Item not found.'
            ], 404);
        }

        $item->update([
            'quantity' => $validated['quantity']
        ]);

        return response()->json([
            'message' => 'Quantity updated.'
        ]);
    }

    public function remove($itemId)
    {
        $item = CartItem::find($itemId);

        if (!$item) {
            return response()->json([
                'message' => 'Item not found.'
            ], 404);
        }

        $cart = $item->cart;

        $item->delete();

        if ($cart->items()->count() == 0) {
            $cart->delete();
        }

        return response()->json([
            'message' => 'Item removed.'
        ]);
    }

    public function summary(Request $request)
    {
        $cart = Cart::where('buyer_id', $request->user()->id)->with(['store','items.product'])->first();

        if (!$cart) {
            return response()->json([
                'items' => [],
                'total' => 0
            ]);
        }

        $items = $cart->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'price' => $item->product->price,
                'quantity' => $item->quantity,
                'subtotal' => $item->product->price * $item->quantity
            ];
        });

        $total = $items->sum('subtotal');

        return response()->json([
            'store' => $cart->store,
            'items' => $items,
            'total' => $total
        ]);
    }

    public function clear(Request $request)
    {
        $cart = Cart::where('buyer_id', $request->user()->id)->first();

        if (!$cart) {
            return response()->json([
                'message' => 'Cart is already empty.'
            ], 404);
        }

        $cart->delete();

        return response()->json([
            'message' => 'Cart cleared successfully.'
        ]);
    }
}
