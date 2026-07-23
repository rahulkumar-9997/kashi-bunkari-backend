<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function razorpay(Request $request, CheckoutController $checkout)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');
        $secret = config('services.razorpay.webhook_secret');

        if (!$signature || !$secret) {
            Log::warning('Razorpay webhook: missing signature or secret not configured.');
            return response()->json(['status' => 'ignored'], 200);
        }

        $expected = hash_hmac('sha256', $payload, $secret);
        if (!hash_equals($expected, $signature)) {
            Log::warning('Razorpay webhook: signature mismatch.');
            return response()->json(['status' => 'invalid signature'], 400);
        }

        $data = json_decode($payload, true);
        $event = $data['event'] ?? null;
        Log::info('Razorpay webhook received: ' . $event);

        $payment = $data['payload']['payment']['entity'] ?? null;
        $razorpayOrderId = $payment['order_id'] ?? null;
        $razorpayPaymentId = $payment['id'] ?? null;

        if ($razorpayOrderId) {
            if ($event === 'payment.captured') {
                $checkout->updatePaymentStatus($razorpayOrderId, $razorpayPaymentId, true);
            } elseif ($event === 'payment.failed') {
                $reason = $payment['error_description'] ?? 'Payment failed';
                $checkout->updatePaymentStatus($razorpayOrderId, $razorpayPaymentId, false, $reason);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}