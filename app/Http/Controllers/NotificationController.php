<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function index($userId)
    {
        try {
            $notifications = Notification::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 1,
                'notifications' => $notifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Error retrieving notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUnread($userId)
    {
        try {
            $notifications = Notification::where('user_id', $userId)
                ->where('is_read', false)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 1,
                'notifications' => $notifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Error retrieving notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPaymentNotifications($userId, Request $request)
    {
        try {
            $lastCheck = $request->query('last_check');

            $query = Notification::where('user_id', $userId)
                ->where('type', 'payment_status')
                ->orderBy('created_at', 'desc');

            if ($lastCheck) {
                $query->where('created_at', '>', $lastCheck);
            }

            $notifications = $query->get();

            return response()->json([
                'status' => 1,
                'notifications' => $notifications,
                'server_time' => Carbon::now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Error retrieving payment notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    public function markAsRead(Request $request)
    {
        $this->validate($request, [
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'exists:notifications_elsid,id'
        ]);

        try {
            Notification::whereIn('id', $request->notification_ids)
                ->update(['is_read' => true]);

            return response()->json([
                'status' => 1,
                'message' => 'Notifications marked as read'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Error marking notifications as read: ' . $e->getMessage()
            ], 500);
        }
    }
}
