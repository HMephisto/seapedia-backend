<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Store;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    private function getDeliveryFee($method)
    {
        switch ($method) {
            case 'INSTANT':
                return 20000;
            case 'NEXT_DAY':
                return 15000;
            case 'REGULAR':
            default:
                return 10000;
        }
    }

    public function checkoutSummary(Request $request)
    {
        $validated = $request->validate([
            'delivery_method' => 'required|in:INSTANT,NEXT_DAY,REGULAR'
        ]);

        $buyer = $request->user();
        $cart = Cart::where('buyer_id', $buyer->id)->with('items.product')->first();

        if (!$cart || $cart->items->count() === 0) {
            return response()->json([
                'message' => 'Cart is empty.'
            ], 400);
        }

        $subtotal = 0;
        $items = [];
        foreach ($cart->items as $item) {
            $itemSubtotal = $item->product->price * $item->quantity;
            $subtotal += $itemSubtotal;
            $items[] = [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'price' => $item->product->price,
                'quantity' => $item->quantity,
                'subtotal' => $itemSubtotal
            ];
        }

        $deliveryFee = $this->getDeliveryFee($validated['delivery_method']);
        $ppn = $subtotal * 0.12;
        $finalTotal = $subtotal + $deliveryFee + $ppn;

        return response()->json([
            'store_id' => $cart->store_id,
            'items' => $items,
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'ppn' => $ppn,
            'final_total' => $finalTotal,
            'delivery_method' => $validated['delivery_method']
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'delivery_method' => 'required|in:INSTANT,NEXT_DAY,REGULAR'
        ]);

        $buyer = $request->user();
        $cart = Cart::where('buyer_id', $buyer->id)->with('items.product')->first();

        if (!$cart || $cart->items->count() === 0) {
            return response()->json([
                'message' => 'Cart is empty.'
            ], 400);
        }

        $address = Address::where('id', $validated['address_id'])->where('buyer_id', $buyer->id)->first();
        if (!$address) {
            return response()->json([
                'message' => 'Address not found or does not belong to you.'
            ], 404);
        }

        DB::beginTransaction();

        try {
            $subtotal = 0;
            foreach ($cart->items as $item) {
                if ($item->product->stock < $item->quantity) {
                    throw new \Exception("Insufficient stock for product: " . $item->product->name);
                }
                $subtotal += ($item->product->price * $item->quantity);
            }

            $deliveryFee = $this->getDeliveryFee($validated['delivery_method']);
            $ppn = $subtotal * 0.12;
            $finalTotal = $subtotal + $deliveryFee + $ppn;

            $wallet = Wallet::firstOrCreate(['user_id' => $buyer->id], ['balance' => 0]);

            if ($wallet->balance < $finalTotal) {
                throw new \Exception("Insufficient wallet balance.");
            }

            $wallet->balance -= $finalTotal;
            $wallet->save();

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'PAYMENT',
                'amount' => $finalTotal,
                'description' => 'Payment for order'
            ]);

            $order = Order::create([
                'buyer_id' => $buyer->id,
                'store_id' => $cart->store_id,
                'address_id' => $address->id,
                'shipping_recipient_name' => $address->recipient_name,
                'shipping_phone' => $address->phone,
                'shipping_address' => $address->address_detail,
                'delivery_method' => $validated['delivery_method'],
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'ppn_amount' => $ppn,
                'final_total' => $finalTotal,
                'status' => 'PACKAGING',
                'expired_at' => now()->addDays(1)
            ]);

            foreach ($cart->items as $item) {
                $product = $item->product;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $product->price,
                    'quantity' => $item->quantity,
                    'subtotal' => $product->price * $item->quantity
                ]);

                $product->stock -= $item->quantity;
                $product->save();
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'PACKAGING',
                'notes' => 'Order placed and paid successfully.'
            ]);

            $cart->items()->delete();
            $cart->delete();

            DB::commit();

            return response()->json([
                'message' => 'Order created successfully.',
                'order_id' => $order->id
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Checkout failed.',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function buyerIndex(Request $request)
    {
        $orders = Order::where('buyer_id', $request->user()->id)
            ->with(['store', 'items'])
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($orders);
    }

    public function buyerShow(Request $request, $id)
    {
        $order = Order::where('buyer_id', $request->user()->id)
            ->with(['store', 'items', 'history'])
            ->findOrFail($id);
        return response()->json($order);
    }

    public function sellerIndex(Request $request)
    {
        $seller = $request->user();
        $store = Store::where('seller_id', $seller->id)->first();
        
        if (!$store) {
            return response()->json([
                'message' => 'Store not found.'
            ], 404);
        }

        $orders = Order::where('store_id', $store->id)
            ->with(['items'])
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($orders);
    }
}
