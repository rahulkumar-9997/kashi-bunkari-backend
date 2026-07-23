<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function show(Request $request, $id)
    {
        $customer = $request->user();

        $order = Order::with(['orderStatus', 'orderAddress', 'orderLine.product.firstSortedImage'])
            ->where('id', $id)
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
            'items' => $order->orderLine->map(fn ($line) => [
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