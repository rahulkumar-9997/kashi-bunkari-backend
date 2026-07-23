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

    private function razorpay(): Api
    {
        return new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
    }

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

        if ($customer && $request->boolean('save_address') && !$request->input('address_id')) {
            $isFirst = Address::where('customer_id', $customer->id)->doesntExist();
            Address::create(array_merge($addressData, [
                'customer_id' => $customer->id,
                'is_default' => $isFirst,
            ]));
        }

        $isGuest = $customer === null;
        $email = $request->input('email');
        $guestCartToken = $this->guestCartToken($request);

        if ($request->input('payment_method') === 'cod') {
            $order = $this->createOrder(
                $customer,
                $addressData,
                $cartLines,
                'cod',
                false,
                null,
                null,
                $email,
                $isGuest,
                $guestCartToken,
            );

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully.',
                'data' => ['order_id' => $order->id, 'order_number' => $order->order_number],
            ]);
        }
        
        $amountPaise = 0;
        foreach ($cartLines as $line) {
            $offerPricePaise = (int) round(($line['inventory']->offer_rate ?? $line['inventory']->mrp ?? 0) * 100);
            $amountPaise += $offerPricePaise * $line['quantity'];
        }

        try {
            $razorpayOrder = $this->razorpay()->order->create([
                'receipt' => 'rcpt_' . ($customer->id ?? 'guest') . '_' . now()->timestamp,
                'amount' => $amountPaise,
                'currency' => 'INR',
                'notes' => ['email' => $email],
            ]);
        } catch (\Throwable $e) {
            Log::error('Razorpay order creation failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Could not initiate payment. Please try again.'], 500);
        }

        try {
            $order = DB::transaction(function () use (
                $customer,
                $addressData,
                $cartLines,
                $email,
                $isGuest,
                $guestCartToken,
                $razorpayOrder
            ) {
                return $this->createOrder(
                    $customer,
                    $addressData,
                    $cartLines,
                    'razorpay',
                    false,
                    $razorpayOrder['id'],
                    null,
                    $email,
                    $isGuest,
                    $guestCartToken,
                );
            });
        } catch (\Throwable $e) {
            Log::error('Local order creation failed after Razorpay order created: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Could not initiate payment. Please try again.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order created — proceed to payment.',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $amountPaise,
                'currency' => 'INR',
                'key' => config('services.razorpay.key'),
            ],
        ]);
    }

    /**
     * Frontend Razorpay handler se call hota hai payment success ke baad.
     * Order ALREADY DB mein hai (placeOrder ke waqt bani thi) — bas
     * payment_received=true aur razorpay_payment_id UPDATE karte hain.
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

        $order = Order::where('razorpay_order_id', $request->razorpay_order_id)->first();
        if (!$order) {
            Log::error('verifyPayment: no local order found for ' . $request->razorpay_order_id);
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        try {
            $this->razorpay()->utility->verifyPaymentSignature([
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ]);
        } catch (SignatureVerificationError $e) {
            Log::warning('Razorpay signature verification failed: ' . $e->getMessage());
            $order->update(['payment_fail_reason' => 'Signature verification failed']);
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed.',
                'data' => ['order_id' => $order->id, 'order_number' => $order->order_number],
            ], 400);
        }

        if (!$order->payment_received) {
            $order->update([
                'payment_received' => true,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'payment_fail_reason' => null,
                'order_completed' =>true
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment verified successfully.',
            'data' => ['order_id' => $order->id, 'order_number' => $order->order_number],
        ]);
    }

    /**
     * Razorpay webhook (payment.captured / payment.failed) se call hota hai.
     * Order already maujood hai — bas status update karta hai. Idempotent.
     */
    public function updatePaymentStatus(string $razorpayOrderId, ?string $razorpayPaymentId, bool $received, ?string $failReason = null): ?Order
    {
        $order = Order::where('razorpay_order_id', $razorpayOrderId)->first();
        if (!$order) {
            Log::error('Webhook: no local order found for ' . $razorpayOrderId);
            return null;
        }

        if ($received) {
            if (!$order->payment_received) {
                $order->update([
                    'payment_received' => true,
                    'razorpay_payment_id' => $razorpayPaymentId ?? $order->razorpay_payment_id,
                    'payment_fail_reason' => null,
                    'order_completed' =>true
                ]);
            }
        } else {
            $order->update(['payment_fail_reason' => $failReason]);
        }

        return $order->fresh();
    }

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

    private function createOrder(
        ?Customer $customer,
        array $addressData,
        array $cartLines,
        string $paymentMode,
        bool $paymentReceived,
        ?string $razorpayOrderId,
        ?string $razorpayPaymentId,
        string $email,
        bool $isGuest,
        ?string $guestCartToken,
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
            'order_number' => uniqid('TMP'),          
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
            'order_completed' => $paymentMode === 'cod' ? true : false,
        ]);
        $order->update([
            'order_number' => 'KB' . now()->format('Y') . str_pad($order->id, 10, '0', STR_PAD_LEFT),
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

        if (!$isGuest) {
            Cart::where('customer_id', $customer->id)->delete();
        } else {
            $this->clearGuestCartByToken($guestCartToken);
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

    private function clearGuestCartByToken(?string $token): void
    {
        if ($token) {
            Cache::forget($this->guestCartCacheKey($token));
            return;
        }
        session()->forget(self::SESSION_KEY);
    }
}
