<?php

namespace App\Http\Controllers\deliverymanager;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function getDeliveryOrders()
{
    $orders = PurchaseOrder::where('want_delivery', 'yes') // تأكد من استخدام القيمة الصحيحة 'yes'
        ->with(['customer.user:id,name', 'itemOrders', 'roomOrders']) // تحميل العلاقة بين Customer و User
        ->get()
        ->map(function ($order) {
            return [
                'purchase_order_id' => $order->id, // إرجاع purchase_order_id
                'customer_id' => $order->customer_id, // إرجاع customer_id
                'customer_name' => $order->customer->user->name, // إرجاع اسم الزبون من User
                'customer_phone' => $order->customer->phone_number, // إرجاع رقم موبايل الزبون من Customer
                'delivery_address' => $order->address, // إرجاع عنوان التوصيل
            ];
        });

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