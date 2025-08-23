<?php

namespace App\Http\Controllers\supermanager;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function getAllOrders()
    {
        $customerId = auth()->user()->customer->id;
        $orders = PurchaseOrder::select('id', 'status', 'recive_date', 'price_after_rabbon', 'is_paid')
            ->where('customer_id', $customerId)
            ->get()
            ->map(function ($order) {
                $remainingTime = '00:00';


                if ($order->recive_date) {
                    $reciveDate = Carbon::parse($order->recive_date)->endOfDay();
                    $remainingTime = Carbon::now()->diffForHumans($reciveDate, [
                        'parts' => 2,
                        'short' => true,
                        'syntax' => CarbonInterface::DIFF_RELATIVE_TO_NOW,
                    ]);
                }
                $remainingAmount = $order->price_after_rabbon;

                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'remaining_time' => (float) $remainingTime,
                    'remaining_bill' => (float) $remainingAmount,
                    'is_paid' => $order->is_paid ? 'Paid in full' : 'Not paid in full',
                ];
            });

        return response()->json([
            'message' => 'Orders retrieved successfully.',
            'orders' => $orders,
        ]);
    }
}