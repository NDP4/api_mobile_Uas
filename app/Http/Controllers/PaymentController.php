<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            $notificationBody = json_decode($request->getContent(), true);

            // Create notification instance from raw post
            $notification = new Notification();

            // Extract notification data
            $orderId = $notificationBody['order_id'];
            $transactionStatus = $notificationBody['transaction_status'];
            $fraudStatus = isset($notificationBody['fraud_status']) ? $notificationBody['fraud_status'] : null;
            $transactionId = $notificationBody['transaction_id'];
            $paymentType = $notificationBody['payment_type'];

            // Extract order ID from format "ORDER-{id}-{timestamp}"
            $realOrderId = explode('-', $orderId)[1];

            // Find the order
            $order = Order::findOrFail($realOrderId);

            $paymentStatus = 'unpaid';
            $orderStatus = 'pending';

            if ($transactionStatus == 'capture') {
                if ($paymentType == 'credit_card') {
                    if ($fraudStatus == 'challenge') {
                        $paymentStatus = 'pending';
                        $orderStatus = 'pending';
                    } else {
                        $paymentStatus = 'paid';
                        $orderStatus = 'processing';
                    }
                }
            } else if ($transactionStatus == 'settlement') {
                $paymentStatus = 'paid';
                $orderStatus = 'processing';
            } else if ($transactionStatus == 'pending') {
                $paymentStatus = 'pending';
                $orderStatus = 'pending';
            } else if ($transactionStatus == 'deny') {
                $paymentStatus = 'failed';
                $orderStatus = 'cancelled';
            } else if ($transactionStatus == 'expire') {
                $paymentStatus = 'expired';
                $orderStatus = 'cancelled';
            } else if ($transactionStatus == 'cancel') {
                $paymentStatus = 'failed';
                $orderStatus = 'cancelled';
            }

            DB::beginTransaction();

            // Update order status
            $order->update([
                'payment_status' => $paymentStatus,
                'status' => $orderStatus,
                'payment_type' => $paymentType,
                'transaction_id' => $transactionId
            ]);

            DB::commit();

            return response()->json([
                'status' => 1,
                'message' => 'Payment notification handled successfully',
                'data' => [
                    'order_id' => $realOrderId,
                    'payment_status' => $paymentStatus,
                    'order_status' => $orderStatus
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Midtrans callback error: ' . $e->getMessage());
            Log::error('Request body: ' . $request->getContent());

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
