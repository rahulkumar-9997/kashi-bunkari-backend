<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $customer = $request->user();
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Please log in.'], 401);
        }

        $perPage = (int) $request->input('per_page', 10);
        $orders = Order::with(['orderStatus', 'orderLines.product.firstSortedImage'])
            ->where('customer_id', $customer->id)
            ->orderByDesc('order_date')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Orders fetched successfully.',
            'data' => [
                'orders' => $orders->getCollection()->map(fn($order) => $this->formatOrderSummary($order))->values(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'total_pages' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total_orders' => $orders->total(),
                    'has_next_page' => $orders->hasMorePages(),
                ],
            ],
        ]);
    }

    private function formatOrderSummary(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'order_date' => $order->order_date,
            'status' => $order->orderStatus?->name,
            'status_color' => $order->orderStatus?->color,
            'payment_mode' => $order->payment_mode,
            'payment_received' => (bool) $order->payment_received,
            'grand_total' => $order->grand_total,
            'item_count' => $order->orderLines->count(),
            'preview_items' => $order->orderLines->take(3)->map(fn ($line) => [
                'title' => $line->product_name,
                'image' => $line->product?->firstSortedImage
                    ? $line->product->firstSortedImage->getSmallImages()
                    : null,
            ])->values(),
        ];
    }


    public function show(Request $request, $order_number)
    {
        $customer = $request->user();

        $order = Order::with(['orderStatus', 'orderAddress', 'orderLine.product.firstSortedImage'])
            ->where('order_number', $order_number)
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        if ($customer && $order->customer_id !== $customer->id) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order fetched successfully.',
            'data' => $this->formatOrder($order),
        ]);
    }

    private function formatOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'order_date' => $order->order_date,
            'status' => $order->orderStatus?->name,
            'status_color' => $order->orderStatus?->color,
            'payment_mode' => $order->payment_mode,
            'payment_received' => (bool) $order->payment_received,
            'payment_fail_reason' => $order->payment_fail_reason,
            'subtotal' => $order->subtotal,
            'shipping_amount' => $order->shipping_amount,
            'tax_amount' => $order->tax_amount,
            'grand_total' => $order->grand_total,
            'address' => $order->orderAddress ? [
                'full_name' => $order->orderAddress->full_name,
                'phone_number' => $order->orderAddress->phone_number,
                'address' => $order->orderAddress->address,
                'locality' => $order->orderAddress->locality,
                'city' => $order->orderAddress->city,
                'state' => $order->orderAddress->state,
                'pin_code' => $order->orderAddress->pin_code,
                'landmark' => $order->orderAddress->landmark,
            ] : null,
            'items' => $order->orderLine->map(fn($line) => [
                'product_id' => $line->product_id,
                'title' => $line->product_name,
                'sku' => $line->sku,
                'quantity' => $line->quantity,
                'price' => $line->price,
                'total_price' => $line->total_price,
                'image' => $line->product?->firstSortedImage
                    ? $line->product->firstSortedImage->getSmallImages()
                    : null,
                'slug' => $line->product?->slug,
            ]),
        ];
    }
}
