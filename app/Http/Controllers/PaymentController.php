<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;

class PaymentController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
        Config::$isSanitized = env('MIDTRANS_SANITIZED', true);
        Config::$is3ds = env('MIDTRANS_3DS', true);
    }

    public function createPayment(Request $request)
    {
        $this->validate($request, [
            'order_id' => 'required|exists:orders_elsid,id'
        ]);

        try {
            $order = Order::with(['user', 'items.product'])->findOrFail($request->order_id);

            $items = [];
            foreach ($order->items as $item) {
                $items[] = [
                    'id' => $item->product_id,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'name' => $item->product->title
                ];
            }

            $transaction_details = [
                'order_id' => 'ORDER-' . $order->id . '-' . time(),
                'gross_amount' => $order->total_amount
            ];

            $customer_details = [
                'first_name' => $order->user->fullname,
                'email' => $order->user->email,
                'phone' => $order->user->phone,
                'shipping_address' => [
                    'address' => $order->shipping_address,
                    'city' => $order->shipping_city,
                    'postal_code' => $order->shipping_postal_code
                ]
            ];

            $transaction_data = [
                'transaction_details' => $transaction_details,
                'customer_details' => $customer_details,
                'item_details' => $items
            ];

            $snapToken = Snap::getSnapToken($transaction_data);
            $paymentUrl = Snap::createTransaction($transaction_data)->redirect_url;

            $order->update([
                'payment_token' => $snapToken,
                'payment_url' => $paymentUrl
            ]);

            return response()->json([
                'status' => 1,
                'message' => 'Payment initialized successfully',
                'data' => [
                    'token' => $snapToken,
                    'payment_url' => $paymentUrl
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Payment initialization failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function handleNotification(Request $request)
    {
        try {
            $notification = new Notification();

            $transaction = $notification->transaction_status;
            $fraud = $notification->fraud_status;
            $orderId = str_replace('ORDER-', '', explode('-', $notification->order_id)[1]);

            $paymentStatus = 'unpaid';

            if ($transaction == 'capture') {
                $paymentStatus = ($fraud == 'challenge') ? 'pending' : 'paid';
            } else if ($transaction == 'settlement') {
                $paymentStatus = 'paid';
            } else if (in_array($transaction, ['cancel', 'deny', 'expire'])) {
                $paymentStatus = 'expired';
            } else if ($transaction == 'pending') {
                $paymentStatus = 'unpaid';
            }

            $order = Order::findOrFail($orderId);
            $order->update(['payment_status' => $paymentStatus]);

            return response()->json(['status' => 1, 'message' => 'Notification handled']);
        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 500);
        }
    }

    public function checkStatus($orderId)
    {
        try {
            $order = Order::select('payment_status', 'payment_url')->findOrFail($orderId);

            return response()->json([
                'status' => 1,
                'data' => [
                    'payment_status' => $order->payment_status,
                    'payment_url' => $order->payment_url
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'message' => 'Order not found'], 404);
        }
    }
}
