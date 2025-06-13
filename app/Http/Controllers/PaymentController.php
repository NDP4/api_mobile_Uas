<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
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
        // $this->validate($request, [
        //     'order_id' => 'required|exists:orders_elsid,id'
        // ]);
        // $this->validate($request, [
        //     'order_id' => 'required|exists:orders_elsid,id',
        //     'total_amount' => 'required|numeric',
        //     'customer_name' => 'required',
        //     'customer_email' => 'required|email',
        //     'shipping_address' => 'required',
        //     'shipping_city' => 'required',
        //     'shipping_postal_code' => 'required'
        // ]);
        $this->validate($request, [
            'order_id' => 'required|exists:orders_elsid,id',
            'user_id' => 'required|exists:users_elsid,id',
            'total_amount' => 'required|numeric',
            'customer_name' => 'required',
            'customer_email' => 'required|email',
            'customer_phone' => 'nullable',
            'shipping_address' => 'required',
            'shipping_city' => 'required',
            'shipping_postal_code' => 'required',
            'items' => 'required|array'
        ]);

        try {
            $this->validate($request, [
                'user_id' => 'required|exists:users_elsid,id'
            ]);

            $order = Order::where('id', $request->order_id)
                ->where('user_id', $request->user_id)
                ->with(['user', 'items.product', 'couponUsage.coupon'])
                ->firstOrFail();

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

            // Add coupon discount as a separate item if exists
            if ($order->couponUsage) {
                $items[] = [
                    'id' => 'DISCOUNT',
                    'price' => -$order->couponUsage->discount_amount,
                    'quantity' => 1,
                    'name' => 'Discount (Coupon: ' . $order->couponUsage->coupon->code . ')'
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
            Log::info('Midtrans Notification:', ['data' => $notificationBody ?? []]);

            if (!$notificationBody) {
                Log::error('Empty notification body');
                return response()->json(['status' => 0, 'message' => 'Invalid notification data'], 400);
            }

            // Verify signature key
            $orderId = $notificationBody['order_id'];
            $statusCode = $notificationBody['status_code'];
            $grossAmount = $notificationBody['gross_amount'];
            $serverKey = Config::$serverKey;
            $input = $orderId . $statusCode . $grossAmount . $serverKey;
            $calculatedSignature = openssl_digest($input, 'sha512');

            if ($calculatedSignature !== ($notificationBody['signature_key'] ?? '')) {
                Log::error('Invalid signature key');
                return response()->json(['status' => 0, 'message' => 'Invalid signature'], 400);
            }

            // Get notification data
            $transactionStatus = $notificationBody['transaction_status'];
            $transactionId = $notificationBody['transaction_id'];
            $fraudStatus = $notificationBody['fraud_status'] ?? null;
            $paymentType = $notificationBody['payment_type'];
            $settlementTime = $notificationBody['settlement_time'] ?? null;

            Log::info('Notification Data:', [
                'order_id' => $orderId,
                'status_code' => $statusCode,
                'transaction_status' => $transactionStatus,
                'payment_type' => $paymentType
            ]);

            // Extract real order ID from format "ORDER-{id}-{timestamp}"
            $realOrderId = explode('-', $orderId)[1];

            // Find the order
            $order = Order::findOrFail($realOrderId);

            $paymentStatus = 'unpaid';
            $orderStatus = 'pending';

            // Handle transaction status based on Midtrans documentation
            $paymentStatus = 'unpaid';
            $orderStatus = 'pending';

            // Transaction is successful if:
            // 1. transaction_status is settlement/capture
            // 2. status_code is 200
            // 3. fraud_status is accept (or null for payment methods without fraud detection)
            if ($statusCode == "200") {
                if ($transactionStatus == "capture") {
                    if ($fraudStatus == "challenge") {
                        $paymentStatus = 'pending';
                        $orderStatus = 'pending';
                    } else if ($fraudStatus == "accept" || $fraudStatus == null) {
                        $paymentStatus = 'paid';
                        $orderStatus = 'processing';
                    }
                } else if ($transactionStatus == "settlement") {
                    $paymentStatus = 'paid';
                    $orderStatus = 'processing';
                }
            }

            // Handle other transaction statuses
            if ($transactionStatus == "pending") {
                $paymentStatus = 'pending';
                $orderStatus = 'pending';
            } else if ($transactionStatus == "deny") {
                $paymentStatus = 'failed';
                $orderStatus = 'cancelled';
            } else if ($transactionStatus == "cancel") {
                $paymentStatus = 'failed';
                $orderStatus = 'cancelled';
            } else if ($transactionStatus == "expire") {
                $paymentStatus = 'expired';
                $orderStatus = 'cancelled';
            } else if ($transactionStatus == "failure") {
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

            // Create notification for user
            \App\Models\Notification::create([
                'user_id' => $order->user_id,
                'title' => 'Payment Status Updated',
                'message' => "Payment for order #$order->id is now $paymentStatus",
                'type' => 'payment_status',
                'data' => [
                    'order_id' => $order->id,
                    'payment_status' => $paymentStatus,
                    'transaction_id' => $transactionId,
                    'payment_type' => $paymentType
                ],
                'order_id' => $order->id,
                'is_read' => false
            ]);

            DB::commit();

            Log::info('Successfully processed payment notification', [
                'order_id' => $realOrderId,
                'payment_status' => $paymentStatus
            ]);

            return response()->json([
                'status' => 1,
                'message' => 'OK',
                'data' => [
                    'order_id' => $realOrderId,
                    'status' => $paymentStatus
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment notification error: ' . $e->getMessage());
            Log::error('Raw notification: ' . $request->getContent());

            return response()->json([
                'status' => 0,
                'message' => 'Error processing payment notification'
            ], 500);
        }
    }

    public function handleRecurringNotification(Request $request)
    {
        try {
            $notificationBody = json_decode($request->getContent(), true);
            Log::info('Midtrans Recurring Notification:', ['data' => $notificationBody ?? []]);

            if (!$notificationBody) {
                Log::error('Empty recurring notification body');
                return response()->json(['status' => 0, 'message' => 'Invalid notification data'], 400);
            }

            // Verify signature key
            $orderId = $notificationBody['order_id'];
            $statusCode = $notificationBody['status_code'];
            $grossAmount = $notificationBody['gross_amount'];
            $serverKey = Config::$serverKey;
            $input = $orderId . $statusCode . $grossAmount . $serverKey;
            $calculatedSignature = openssl_digest($input, 'sha512');

            if ($calculatedSignature !== ($notificationBody['signature_key'] ?? '')) {
                Log::error('Invalid signature key for recurring notification');
                return response()->json(['status' => 0, 'message' => 'Invalid signature'], 400);
            }

            // Get notification data
            $transactionStatus = $notificationBody['transaction_status'];
            $transactionId = $notificationBody['transaction_id'];
            $fraudStatus = $notificationBody['fraud_status'] ?? null;
            $paymentType = $notificationBody['payment_type'];

            // Extract real order ID from format "ORDER-{id}-{timestamp}"
            $realOrderId = explode('-', $orderId)[1];

            // Find the order
            $order = Order::findOrFail($realOrderId);

            $paymentStatus = 'unpaid';
            $orderStatus = 'pending';

            // Handle transaction status
            if ($statusCode == "200" && ($transactionStatus == "capture" || $transactionStatus == "settlement")) {
                if ($fraudStatus == "accept" || $fraudStatus == null) {
                    $paymentStatus = 'paid';
                    $orderStatus = 'processing';
                }
            }

            DB::beginTransaction();

            // Update order status
            $order->update([
                'payment_status' => $paymentStatus,
                'status' => $orderStatus,
                'payment_type' => $paymentType,
                'transaction_id' => $transactionId
            ]);

            // Create notification for user
            \App\Models\Notification::create([
                'user_id' => $order->user_id,
                'title' => 'Recurring Payment Status Updated',
                'message' => "Recurring payment for order #$order->id is now $paymentStatus",
                'type' => 'recurring_payment_status',
                'data' => [
                    'order_id' => $order->id,
                    'payment_status' => $paymentStatus,
                    'transaction_id' => $transactionId,
                    'payment_type' => $paymentType
                ],
                'order_id' => $order->id,
                'is_read' => false
            ]);

            DB::commit();

            return response()->json([
                'status' => 1,
                'message' => 'OK',
                'data' => [
                    'order_id' => $realOrderId,
                    'status' => $paymentStatus
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Recurring payment notification error: ' . $e->getMessage());
            Log::error('Raw notification: ' . $request->getContent());

            return response()->json([
                'status' => 0,
                'message' => 'Error processing recurring payment notification'
            ], 500);
        }
    }

    public function checkStatus(Request $request, $orderId, $userId)
    {
        try {
            // Validate user exists
            $this->validate($request, [
                'user_id' => 'exists:users_elsid,id'
            ]);

            // Find order and verify ownership
            $order = Order::where('id', $orderId)
                ->where('user_id', $userId)
                ->firstOrFail();

            // Get status from Midtrans API
            $serverKey = Config::$serverKey;
            $baseUrl = Config::$isProduction ?
                'https://api.midtrans.com' :
                'https://api.sandbox.midtrans.com';

            $midtransOrderId = 'ORDER-' . $order->id . '-' . strtotime($order->created_at);
            $url = $baseUrl . '/v2/' . $midtransOrderId . '/status';

            $auth = base64_encode($serverKey . ':');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Basic ' . $auth
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode == 200) {
                $result = json_decode($response, true);

                // Update order status based on Midtrans status
                $transactionStatus = $result['transaction_status'];
                $fraudStatus = $result['fraud_status'] ?? null;

                $paymentStatus = 'unpaid';
                $orderStatus = 'pending';

                if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
                    if ($fraudStatus == 'accept' || $fraudStatus == null) {
                        $paymentStatus = 'paid';
                        $orderStatus = 'processing';

                        // Update shipping tracking if payment is successful
                        $this->updateShippingTracking($order);
                    }
                }

                $order->update([
                    'payment_status' => $paymentStatus,
                    'status' => $orderStatus
                ]);

                return response()->json([
                    'status' => 1,
                    'data' => array_merge(
                        $result,
                        [
                            'payment_status' => $paymentStatus,
                            'payment_url' => $order->payment_url
                        ]
                    )
                ]);
            }

            return response()->json([
                'status' => 1,
                'data' => [
                    'payment_status' => $order->payment_status,
                    'payment_url' => $order->payment_url
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Error checking status: ' . $e->getMessage()
            ], 500);
        }
    }

    private function updateShippingTracking($order)
    {
        $tracking = \App\Models\ShippingTracking::where('order_id', $order->id)->first();
        if ($tracking) {
            $now = Carbon::now();
            $tracking->status = 'processing';
            $tracking->shipping_start_date = $now;
            $tracking->estimated_arrival = $now->addDays($tracking->etd_days);
            $tracking->tracking_history = json_encode([
                [
                    'status' => 'order_received',
                    'date' => $now->format('Y-m-d H:i:s'),
                    'description' => 'Order has been received and is being processed'
                ]
            ]);
            $tracking->save();
        }
    }
}
