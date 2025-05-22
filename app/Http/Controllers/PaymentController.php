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

            // Add shipping cost as a separate item
            if ($order->shipping_cost > 0) {
                $items[] = [
                    'id' => 'SHIPPING',
                    'price' => $order->shipping_cost,
                    'quantity' => 1,
                    'name' => 'Shipping Cost (' . $order->courier . ' - ' . $order->courier_service . ')'
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

            $order_id = str_replace('ORDER-', '', explode('-', $notification->order_id)[1]);
            $transaction_status = $notification->transaction_status;
            $transaction_id = $notification->transaction_id;
            $fraud_status = $notification->fraud_status;
            $payment_type = $notification->payment_type;

            $order = Order::findOrFail($order_id);
            $payment_status = 'unpaid';
            $order_status = 'pending';

            if ($transaction_status == 'capture') {
                if ($payment_type == 'credit_card') {
                    if ($fraud_status == 'challenge') {
                        $payment_status = 'pending';
                        $order_status = 'pending';
                    } else {
                        $payment_status = 'paid';
                        $order_status = 'processing';
                    }
                }
            } else if ($transaction_status == 'settlement') {
                $payment_status = 'paid';
                $order_status = 'processing';
            } else if ($transaction_status == 'pending') {
                $payment_status = 'pending';
                $order_status = 'pending';
            } else if ($transaction_status == 'deny') {
                $payment_status = 'failed';
                $order_status = 'cancelled';
            } else if ($transaction_status == 'expire') {
                $payment_status = 'expired';
                $order_status = 'cancelled';
            } else if ($transaction_status == 'cancel') {
                $payment_status = 'failed';
                $order_status = 'cancelled';
            }

            DB::beginTransaction();

            // Update order payment status
            $order->update([
                'payment_status' => $payment_status,
                'status' => $order_status,
                'payment_type' => $payment_type,
                'transaction_id' => $transaction_id
            ]);

            DB::commit();

            return response()->json([
                'status' => 1,
                'message' => 'Payment notification handled successfully',
                'data' => [
                    'order_id' => $order_id,
                    'payment_status' => $payment_status,
                    'order_status' => $order_status
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 0,
                'message' => 'Error handling payment notification: ' . $e->getMessage()
            ], 500);
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
