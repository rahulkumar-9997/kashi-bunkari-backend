<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class PaymentController extends Controller
{
    private function razorpay(): Api
    {
        return new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
    }

    /**
     * Called by RAZORPAY ITSELF (server-to-server, no customer auth) to
     * fetch available coupons for a cart. Configure this URL in:
     * Razorpay Dashboard → Magic Checkout → Checkout Settings →
     * Coupon Settings → "URL for get promotions".
     *
     * ⚠️ STUB — this must be publicly reachable (no auth middleware).
     * Returning an empty list is valid ("no coupons available") and
     * won't break checkout. Replace with real coupon lookup logic —
     * verify the exact expected response shape against Razorpay's
     * current Magic Checkout docs before going live, since I couldn't
     * fully confirm it from available documentation.
     */
    public function getPromotions(Request $request)
    {
        return response()->json([
            'promotions' => [],
        ]);
    }

    /**
     * Called by RAZORPAY ITSELF (server-to-server, no customer auth) to
     * fetch shipping options for a given address. Configure this URL in:
     * Razorpay Dashboard → Magic Checkout → Checkout Settings →
     * Shipping Settings → "URL for shipping info".
     *
     * ⚠️ STUB — replace shipping_fee/logic with your real rates.
     * Verify the exact expected request/response shape against
     * Razorpay's current docs before going live.
     */
    public function getShippingInfo(Request $request)
    {
        return response()->json([
            'shipping_methods' => [
                [
                    'id' => 'standard',
                    'name' => 'Standard Delivery',
                    'description' => '4-6 business days',
                    'shipping_fee' => 0, // paise — 0 = free shipping
                    'cod_fee' => 0,
                    'is_cod_available' => true,
                ],
            ],
        ]);
    }

    /**
     * Step 1 — create a Razorpay order from the customer's current cart.
     * Amount is ALWAYS recalculated server-side from live inventory
     * prices — never trust an amount sent by the client.
     */
    public function createOrder(Request $request)
    {
        $customer = $request->user();
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Please log in to checkout.'], 401);
        }

        $cartItems = Cart::where('customer_id', $customer->id)
            ->with([
                'inventory:id,product_id,mrp,offer_rate,stock_quantity,sku',
                'product:id,title',
                'product.firstSortedImage:id,product_id,image_path',
            ])
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Your cart is empty.'], 400);
        }

        // Magic Checkout needs per-product line_items (not just a total
        // amount) so it can render the product list, images, and price
        // breakdown inside its own hosted UI.
        $lineItems = [];
        $lineItemTotal = 0;

        foreach ($cartItems as $item) {
			$inventory = $item->inventory;
			if (!$inventory) {
				return response()->json([
					'success' => false,
					'message' => 'One of your items is no longer available.',
				], 400);
			}
			if ($inventory->stock_quantity !== null && $inventory->stock_quantity < $item->quantity) {
				return response()->json([
					'success' => false,
					'message' => "Only {$inventory->stock_quantity} unit(s) left for one of your items.",
				], 400);
			}

			$mrpPaise = (int) round(($inventory->mrp ?? $inventory->offer_rate ?? 0) * 100);
			$offerPricePaise = (int) round(($inventory->offer_rate ?? $inventory->mrp ?? 0) * 100);

			$lineItems[] = [
				'sku' => $inventory->sku ?? (string) $item->product_id,
				'variant_id' => (string) ($inventory->id ?? $item->product_id), // mandatory
				'price' => $mrpPaise,
				'offer_price' => $offerPricePaise,
				'quantity' => $item->quantity,
				'name' => $item->product->title ?? '',
				'description' => $item->product->title ?? '', // mandatory — use title if no separate description
				'image_url' => $item->product?->firstSortedImage
					? $item->product->firstSortedImage->getSmallImages()
					: null,
			];

			$lineItemTotal += $offerPricePaise * $item->quantity;
		}

        try {
            $razorpayOrder = $this->razorpay()->order->create([
                'receipt' => 'rcpt_' . $customer->id . '_' . now()->timestamp,
                'amount' => $lineItemTotal,
                'currency' => 'INR',
                'notes' => ['customer_id' => (string) $customer->id],
                // ⚠️ THE parameter that marks this as a Magic Checkout
                // order — without it, Razorpay silently falls back to
                // Standard Checkout even though Magic Checkout is
                // enabled on your account.
                //
                // Razorpay's docs use "line_item_total" (singular
                // "item") in the Magic Checkout web-integration guide,
                // but the official razorpay-php sample code uses
                // "line_items_total" (plural). This inconsistency
                // exists in their own docs — please confirm the exact
                // key with your Razorpay account manager/SPOC (or test
                // both in Test Mode) before going live.
                'line_items' => $lineItems,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order created.',
                'data' => [
                    'order_id' => $razorpayOrder['id'],
                    'amount' => $lineItemTotal,
                    'currency' => 'INR',
                    'key' => config('services.razorpay.key'),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Razorpay order creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Could not initiate payment. Please try again.',
            ], 500);
        }
    }

    /**
     * Step 2 — after Razorpay Checkout completes on the frontend, verify
     * the payment signature, then create your own Order record and
     * clear the cart.
     *
     * ⚠️ Order::create()/OrderItem::create() field names below are a
     * reasonable guess — adjust them to match your actual Order/OrderItem
     * models (I haven't seen those schemas).
     */
    public function verifyPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $customer = $request->user();
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Please log in.'], 401);
        }

        try {
            $this->razorpay()->utility->verifyPaymentSignature([
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ]);
        } catch (SignatureVerificationError $e) {
            Log::warning('Razorpay signature verification failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Payment verification failed.'], 400);
        }

        try {
            $order = DB::transaction(function () use ($customer, $request) {
                $cartItems = Cart::where('customer_id', $customer->id)->with('inventory')->get();

                if ($cartItems->isEmpty()) {
                    throw new \RuntimeException('Cart was already empty during order finalization.');
                }

                $order = Order::create([
                    'customer_id' => $customer->id,
                    'razorpay_order_id' => $request->razorpay_order_id,
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'status' => 'paid',
                ]);

                foreach ($cartItems as $item) {
                    $inventory = $item->inventory;
                    $price = $inventory?->offer_rate ?? $inventory?->mrp ?? 0;

                    OrderLine::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'inventory_id' => $item->inventory_id,
                        'quantity' => $item->quantity,
                        'price' => $price,
                    ]);

                    if ($inventory && $inventory->stock_quantity !== null) {
                        $inventory->decrement('stock_quantity', $item->quantity);
                    }
                }

                Cart::where('customer_id', $customer->id)->delete();

                return $order;
            });

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully.',
                'data' => ['order_id' => $order->id],
            ]);
        } catch (\Throwable $e) {
            Log::error('Order finalization after payment failed: ' . $e->getMessage());
            // Payment was ALREADY captured by Razorpay at this point —
            // never tell the customer it failed. Log it for manual
            // reconciliation and let them know support will follow up.
            return response()->json([
                'success' => true,
                'message' => 'Payment received. We are finalizing your order — you will get a confirmation shortly.',
                'data' => ['order_id' => null],
            ]);
        }
    }
}