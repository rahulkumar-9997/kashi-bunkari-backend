<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartService
{
    public static function mergeCartAfterLogin($customerId, $sessionId)
    {
        try {
            DB::beginTransaction();
            Log::info("Cart Merge Started", [
                'customer_id' => $customerId,
                'session_id'  => $sessionId
            ]);
            if (!$sessionId) {
                DB::commit();
                return [
                    'merged_items' => 0,
                    'new_items' => 0
                ];
            }
            $guestCartItems = Cart::where('session_id', $sessionId)->get();
            $mergedCount = 0;
            $newCount = 0;
            foreach ($guestCartItems as $item) {
                $existingCart = Cart::where('customer_id', $customerId)
                    ->where('product_id', $item->product_id)
                    ->first();
                if ($existingCart) {
                    $existingCart->quantity += $item->quantity;
                    $existingCart->save();
                    $item->delete();
                    $mergedCount++;
                } else {
                    $item->update([
                        'customer_id' => $customerId,
                        'session_id'  => null
                    ]);
                    $newCount++;
                }
            }
            DB::commit();
            Log::info("Cart Merge Completed", [
                'merged_items' => $mergedCount,
                'new_items'    => $newCount
            ]);
            return [
                'merged_items' => $mergedCount,
                'new_items'    => $newCount
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Cart Merge Failed", [
                'error' => $e->getMessage(),
                'customer_id' => $customerId,
                'session_id'  => $sessionId
            ]);
            return [
                'merged_items' => 0,
                'new_items' => 0,
                'error' => true
            ];
        }
    }
}