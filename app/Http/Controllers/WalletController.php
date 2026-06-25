<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use DB;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    private function getOrCreateWallet($user): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );
    }

    public function show(Request $request)
    {
        $wallet = $this->getOrCreateWallet($request->user());
 
        return response()->json([
            'success' => true,
            'data' => [
                'id'         => $wallet->id,
                'balance'    => (float) $wallet->balance,
                'created_at' => $wallet->created_at,
                'updated_at' => $wallet->updated_at,
            ],
        ]);
    }
 
    public function topup(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1000|max:10000000',
        ]);
 
        $amount = $validated['amount'];
 
        $wallet = DB::transaction(function () use ($request, $amount) {
            $wallet = $this->getOrCreateWallet($request->user());
 
            $wallet = Wallet::lockForUpdate()->find($wallet->id);
            $wallet->balance = $wallet->balance + $amount;
            $wallet->save();
 
            WalletTransaction::create([
                'wallet_id'   => $wallet->id,
                'type'        => 'TOPUP',
                'amount'      => $amount,
                'description' => 'Top up via app',
                'created_at'  => now(),
            ]);
 
            return $wallet;
        });
 
        return response()->json([
            'success' => true,
            'message' => 'Top up successful.',
            'data' => [
                'new_balance' => (float) $wallet->balance,
                'topped_up'  => (float) $amount,
            ],
        ]);
    }
 
    public function transactions(Request $request)
    {
        $wallet = $this->getOrCreateWallet($request->user());
 
        $query = WalletTransaction::where('wallet_id', $wallet->id)
            ->orderByDesc('created_at');
 
        // Optional filter by type
        if ($request->filled('type')) {
            $request->validate([
                'type' => 'in:TOPUP,PAYMENT,REFUND',
            ]);
            $query->where('type', $request->type);
        }
 
        $transactions = $query->paginate(10);
 
        return response()->json([
            'success' => true,
            'data'    => $transactions->items(),
            'meta'    => [
                'current_page' => $transactions->currentPage(),
                'last_page'    => $transactions->lastPage(),
                'per_page'     => $transactions->perPage(),
                'total'        => $transactions->total(),
            ],
        ]);
    }
}
