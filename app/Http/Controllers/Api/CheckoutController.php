<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderLine;
use App\Models\OrderStatus;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class CheckoutController extends Controller
{
    private const SESSION_KEY = 'cart';
    private const PENDING_ORDER_TTL_MINUTES = 30;

    private function razorpay(): Api
    {
        return new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
    }

    /**
     * Single entry point for placing an order.
     * - payment_method = 'cod'      → order created immediately.
     * - payment_method = 'razorpay' → only a plain Razorpay order is created
     *   here (standard checkout, no Magic Checkout fields). The local Order
     *   row is created ONLY after verifyPayment() confirms success.
     */
    public function placeOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:cod,razorpay',
            'email' => 'required|email',
            'address_id' => 'nullable|integer',
            'address.name' => 'required_without:address_id|string|max:150',
            'address.phone_number' => 'required_without:address_id|string|max:20',
            'address.alternate_phone' => 'nullable|string|max:20',
            'address.zip_code' => 'required_without:address_id|string|max:10',
            'address.locality' => 'required_without:address_id|string|max:150',
            'address.address' => 'required_without:address_id|string|max:255',
            'address.city' => 'required_without:address_id|string|max:100',
            'address.state' => 'required_without:address_id|string|max:100',
            'address.landmark' => 'nullable|string|max:150',
            'address.country' => 'nullable|string|max:100',
            'save_address' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $customer = $request->user();

        $addressData = $this->resolveAddressData($request, $customer);
        if (!$addressData) {
            return response()->json(['success' => false, 'message' => 'Please select or enter a valid address.'], 422);
        }

        $cartLines = $this->getCartLines($request, $customer);
        if (empty($cartLines)) {
            return response()->json(['success' => false, 'message' => 'Your cart is empty.'], 400);
        }

        foreach ($cartLines as $line) {
            if (!$line['inventory']) {
                return response()->json(['success' => false, 'message' => 'One of your items is no longer available.'], 400);
            }
            if ($line['inventory']->stock_quantity !== null && $line['inventory']->stock_quantity < $line['quantity']) {
                return response()->json([
                    'success' => false,
                    'message' => "Only {$line['inventory']->stock_quantity} unit(s) left for one of your items.",
                ], 400);
            }
        }

        // Logged-in customer chahe to naya address apne address book mein bhi save kar sake
        if ($customer && $request->boolean('save_address') && !$request->input('address_id')) {
            $isFirst = Address::where('customer_id', $customer->id)->doesntExist();
            Address::create(array_merge($addressData, [
                'customer_id' => $customer->id,
                'is_default' => $isFirst,
            ]));
        }

        if ($request->input('payment_method') === 'cod') {
            $order = $this->finalizeOrder($request, $customer, $addressData, $cartLines, 'cod', true, null, null, $request->input('email'));
            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully.',
                'data' => ['order_id' => $order->id, 'order_number' => $order->order_number],
            ]);
        }

        // payment_method === 'razorpay' — sirf Razorpay order create hoga
        $amountPaise = 0;
        foreach ($cartLines as $line) {
            $offerPricePaise = (int) round(($line['inventory']->offer_rate ?? $line['inventory']->mrp ?? 0) * 100);
            $amountPaise += $offerPricePaise * $line['quantity'];
        }

        try {
            // ✅ STANDARD Razorpay order — Magic Checkout ke koi fields nahi
            // (no line_items, no line_items_total, no one_click_checkout)
            $razorpayOrder = $this->razorpay()->order->create([
                'receipt' => 'rcpt_' . ($customer->id ?? 'guest') . '_' . now()->timestamp,
                'amount' => $amountPaise,
                'currency' => 'INR',
                'notes' => ['email' => $request->input('email')],
            ]);

            Cache::put(
                'pending_order:' . $razorpayOrder['id'],
                [
                    'customer_id' => $customer?->id,
                    'email' => $request->input('email'),
                    'address' => $addressData,
                    'cart_lines' => array_map(fn($l) => [
                        'product_id' => $l['product_id'],
                        'quantity' => $l['quantity'],
                    ], $cartLines),
                    'is_guest' => $customer === null,
                    'guest_cart_token' => $this->guestCartToken($request),
                ],
                now()->addMinutes(self::PENDING_ORDER_TTL_MINUTES),
            );

            return response()->json([
                'success' => true,
                'message' => 'Razorpay order created.',
                'data' => [
                    'order_id' => $razorpayOrder['id'],
                    'amount' => $amountPaise,
                    'currency' => 'INR',
                    'key' => config('services.razorpay.key'),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Razorpay order creation failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Could not initiate payment. Please try again.'], 500);
        }
    }

    /**
     * Standard Razorpay Checkout ke handler se call hota hai payment success
     * hone ke baad. Signature verify karke, tabhi actual Order banti hai.
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

        $pending = Cache::get('pending_order:' . $request->razorpay_order_id);
        if (!$pending) {
            Log::error('Pending order payload missing for ' . $request->razorpay_order_id);
            // Payment already ho chuka hai — customer ko kabhi "failed" mat batao
            return response()->json([
                'success' => true,
                'message' => 'Payment received. We are finalizing your order — you will get a confirmation shortly.',
                'data' => ['order_id' => null],
            ]);
        }

        try {
            $order = DB::transaction(function () use ($pending, $request) {
                $customer = $pending['customer_id'] ? Customer::find($pending['customer_id']) : null;

                $cartLines = [];
                foreach ($pending['cart_lines'] as $line) {
                    $product = Product::find($line['product_id']);
                    if (!$product) continue;
                    $cartLines[] = [
                        'product_id' => $line['product_id'],
                        'quantity' => $line['quantity'],
                        'inventory' => $this->resolveCheapestInventory($line['product_id']),
                        'product' => $product,
                    ];
                }

                return $this->finalizeOrder(
                    $request,
                    $customer,
                    $pending['address'],
                    $cartLines,
                    'online',
                    true,
                    $request->razorpay_order_id,
                    $request->razorpay_payment_id,
                    $pending['email'],
                    $pending['is_guest'],
                    $pending['guest_cart_token'],
                );
            });

            Cache::forget('pending_order:' . $request->razorpay_order_id);

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully.',
                'data' => ['order_id' => $order->id, 'order_number' => $order->order_number],
            ]);
        } catch (\Throwable $e) {
            Log::error('Order finalization after payment failed: ' . $e->getMessage());
            return response()->json([
                'success' => true,
                'message' => 'Payment received. We are finalizing your order — you will get a confirmation shortly.',
                'data' => ['order_id' => null],
            ]);
        }
    }

    /* =========================================================================
     |  INTERNAL HELPERS
     * ========================================================================= */

    private function resolveAddressData(Request $request, $customer): ?array
    {
        $addressId = $request->input('address_id');
        if ($addressId && $customer) {
            $saved = Address::where('customer_id', $customer->id)->where('id', $addressId)->first();
            if (!$saved) return null;
            return [
                'name' => $saved->name,
                'phone_number' => $saved->phone_number,
                'alternate_phone' => $saved->alternate_phone,
                'zip_code' => $saved->zip_code,
                'locality' => $saved->locality,
                'address' => $saved->address,
                'city' => $saved->city,
                'state' => $saved->state,
                'landmark' => $saved->landmark,
                'country' => $saved->country ?? 'India',
            ];
        }

        $addr = $request->input('address');
        if (!$addr) return null;

        return [
            'name' => $addr['name'] ?? null,
            'phone_number' => $addr['phone_number'] ?? null,
            'alternate_phone' => $addr['alternate_phone'] ?? null,
            'zip_code' => $addr['zip_code'] ?? null,
            'locality' => $addr['locality'] ?? null,
            'address' => $addr['address'] ?? null,
            'city' => $addr['city'] ?? null,
            'state' => $addr['state'] ?? null,
            'landmark' => $addr['landmark'] ?? null,
            'country' => $addr['country'] ?? 'India',
        ];
    }

    /**
     * Logged-in ho to customer already pata hai. Guest ho to email se
     * Customer table mein dhoondho — mile to wahi, na mile to naya banao.
     */
    private function finalizeOrder(
        Request $request,
        ?Customer $customer,
        array $addressData,
        array $cartLines,
        string $paymentMode,
        bool $paymentReceived,
        ?string $razorpayOrderId,
        ?string $razorpayPaymentId,
        string $email,
        ?bool $wasGuest = null,
        ?string $guestCartTokenOverride = null,
    ): Order {
        if (!$customer) {
            $customer = Customer::where('email', $email)->first();
            if (!$customer) {
                $customer = Customer::create([
                    'name' => $addressData['name'],
                    'email' => $email,
                    'customer_id' => Customer::generateCustomerId(),
                    'password' => Hash::make(Str::random(24)),
                    'phone_number' => $addressData['phone_number'],
                    'status' => true,
                ]);
            }
        }

        $orderAddress = OrderAddress::create([
            'customer_id' => $customer->id,
            'type' => 'shipping',
            'full_name' => $addressData['name'],
            'phone_number' => $addressData['phone_number'],
            'alternate_phone' => $addressData['alternate_phone'],
            'email' => $email,
            'country' => $addressData['country'] ?? 'India',
            'address' => $addressData['address'],
            'apartment' => null,
            'city' => $addressData['city'],
            'state' => $addressData['state'],
            'pin_code' => $addressData['zip_code'],
            'locality' => $addressData['locality'],
            'landmark' => $addressData['landmark'],
        ]);

        $subtotal = 0;
        foreach ($cartLines as $line) {
            $price = $line['inventory']->offer_rate ?? $line['inventory']->mrp ?? 0;
            $subtotal += $price * $line['quantity'];
        }

        $pendingStatus = OrderStatus::where('slug', 'pending')->first()
            ?? OrderStatus::orderBy('sort_order')->first();

        $order = Order::create([
            'order_number' => 'ORD' . now()->format('ymd') . strtoupper(Str::random(6)),
            'order_date' => now(),
            'subtotal' => $subtotal,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'grand_total' => $subtotal,
            'payment_mode' => $paymentMode,
            'payment_received' => $paymentReceived,
            'customer_id' => $customer->id,
            'order_address_id' => $orderAddress->id,
            'order_status_id' => $pendingStatus?->id,
            'razorpay_order_id' => $razorpayOrderId,
            'razorpay_payment_id' => $razorpayPaymentId,
        ]);

        foreach ($cartLines as $line) {
            $inventory = $line['inventory'];
            $price = $inventory->offer_rate ?? $inventory->mrp ?? 0;

            OrderLine::create([
                'order_id' => $order->id,
                'product_id' => $line['product_id'],
                'product_name' => $line['product']->title ?? '',
                'sku' => $inventory->sku,
                'quantity' => $line['quantity'],
                'price' => $price,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_price' => $price * $line['quantity'],
            ]);

            if ($inventory->stock_quantity !== null) {
                $inventory->decrement('stock_quantity', $line['quantity']);
            }
        }

        $isGuest = $wasGuest ?? ($request->user() === null);
        if (!$isGuest) {
            Cart::where('customer_id', $customer->id)->delete();
        } else {
            $this->clearGuestCart($request, $guestCartTokenOverride);
        }

        return $order;
    }

    private function getCartLines(Request $request, ?Customer $customer): array
    {
        if ($customer) {
            return Cart::where('customer_id', $customer->id)
                ->with(['inventory', 'product:id,title'])
                ->get()
                ->map(fn($item) => [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'inventory' => $item->inventory,
                    'product' => $item->product,
                ])
                ->values()
                ->all();
        }

        $guestCart = $this->getGuestCart($request);
        $productIds = array_keys($guestCart);
        if (empty($productIds)) return [];

        $products = Product::whereIn('id', $productIds)->where('product_status', 1)->get()->keyBy('id');

        $lines = [];
        foreach ($guestCart as $productId => $entry) {
            $product = $products->get($productId);
            if (!$product) continue;
            $lines[] = [
                'product_id' => (int) $productId,
                'quantity' => (int) ($entry['quantity'] ?? 1),
                'inventory' => $this->resolveCheapestInventory((int) $productId),
                'product' => $product,
            ];
        }
        return $lines;
    }

    private function resolveCheapestInventory(int $productId): ?Inventory
    {
        return Inventory::where('product_id', $productId)->orderBy('mrp', 'asc')->first();
    }

    private function guestCartToken(Request $request): ?string
    {
        $token = $request->header('X-Cart-Token');
        return $token ? trim($token) : null;
    }

    private function guestCartCacheKey(string $token): string
    {
        return "cart:{$token}";
    }

    private function getGuestCart(Request $request): array
    {
        $token = $this->guestCartToken($request);
        if ($token) return Cache::get($this->guestCartCacheKey($token), []);
        return session(self::SESSION_KEY, []);
    }

    private function clearGuestCart(Request $request, ?string $tokenOverride = null): void
    {
        $token = $tokenOverride ?? $this->guestCartToken($request);
        if ($token) {
            Cache::forget($this->guestCartCacheKey($token));
            return;
        }
        session()->forget(self::SESSION_KEY);
    }
}
