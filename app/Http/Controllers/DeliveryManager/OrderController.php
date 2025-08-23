<?php

namespace App\Http\Controllers\deliverymanager;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class OrderController extends Controller
{
        public function getDeliveryOrders()
    {
        $orders = PurchaseOrder::where('want_delivery', true)
            ->with(['customer', 'itemOrders', 'roomOrders'])
            ->get();

        return response()->json([
            'message' => 'want delivery:',
            'data' => $orders
        ]);
    }

    public function updateDeliveryStatus($orderId)
    {
        $order = PurchaseOrder::find($orderId);

        if (!$order) {
            return response()->json([
                'message' => 'order not found',
            ], 404);
        }

        $order->update([
            'is_recived' => 'done'
        ]);

        return response()->json([
            'message' => 'Done ^_^',
            'data' => $order
        ]);
    }

    public function getOrderSchedules()
    {
        $orders = PurchaseOrder::select('id', 'recive_date', 'delivery_time')
            ->whereNotNull('recive_date')
            ->whereNotNull('delivery_time')
            ->get();

        return response()->json([
            'message' => 'All scheduled orders',
            'data' => $orders
        ]);
    }
}