<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ShippingTracking;
use Carbon\Carbon;

class UpdateShippingStatus extends Command
{
    protected $signature = 'shipping:update-status';
    protected $description = 'Update shipping statuses based on estimated delivery time';

    public function handle()
    {
        $trackings = ShippingTracking::where('status', '!=', 'delivered')
            ->where('shipping_start_date', '!=', null)
            ->get();

        foreach ($trackings as $tracking) {
            $now = Carbon::now();
            $startDate = Carbon::parse($tracking->shipping_start_date);
            $estimatedDate = Carbon::parse($tracking->estimated_arrival);
            $daysPassed = $startDate->diffInDays($now);
            $totalDays = $tracking->etd_days;

            // Calculate progress percentage
            $progress = ($daysPassed / $totalDays) * 100;

            if ($now->greaterThan($estimatedDate)) {
                $status = 'delivered';
            } elseif ($progress >= 66) {
                $status = 'out_for_delivery';
            } elseif ($progress >= 33) {
                $status = 'in_transit';
            } else {
                $status = 'processing';
            }

            // Add tracking history
            $history = json_decode($tracking->tracking_history ?? '[]', true);
            if ($tracking->status !== $status) {
                $history[] = [
                    'status' => $status,
                    'date' => $now->format('Y-m-d H:i:s'),
                    'description' => $this->getStatusDescription($status)
                ];
            }

            $tracking->update([
                'status' => $status,
                'tracking_history' => json_encode($history)
            ]);
        }

        $this->info('Shipping statuses updated successfully');
    }

    private function getStatusDescription($status)
    {
        switch ($status) {
            case 'processing':
                return 'Order is being processed';
            case 'in_transit':
                return 'Package is in transit';
            case 'out_for_delivery':
                return 'Package is out for delivery';
            case 'delivered':
                return 'Package has been delivered';
            default:
                return 'Status updated';
        }
    }
}
