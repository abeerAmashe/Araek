<?php

namespace App\Http\Controllers\workshopManager;

use App\Http\Controllers\Controller;
use BilalMardini\FirebaseNotification\Facades\FirebaseNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WorkshopOrderController extends Controller
{
    public function markOrderDetailAsComplete(Request $request)
    {
        $orderTypes = [
            'room_order_id' => \App\Models\RoomOrder::class,
            'item_order_id' => \App\Models\ItemOrder::class,
            'customization_order_id' => \App\Models\CustomizationOrder::class,
            'room_customization_order_id' => \App\Models\RoomCustomizationOrder::class,
        ];

        $order = null;
        $purchaseOrder = null;

        foreach ($orderTypes as $key => $model) {
            if ($request->has($key)) {
                $order = $model::find($request->input($key));
                if (!$order) {
                    return response()->json(['message' => "Invalid {$key}"], 404);
                }

                $order->status = 'complete';
                $order->save();

                $purchaseOrder = $order->purchaseOrder;
                break;
            }
        }

        if (!$order || !$purchaseOrder) {
            return response()->json(['message' => 'No valid order ID provided.'], 400);
        }

        // Check if all related orders are marked as complete
        $allComplete =
            $purchaseOrder->roomOrders()->where('status', '!=', 'complete')->count() === 0 &&
            $purchaseOrder->itemOrders()->where('status', '!=', 'complete')->count() === 0 &&
            $purchaseOrder->customizationOrders()->where('status', '!=', 'complete')->count() === 0 &&
            $purchaseOrder->roomcustomizationOrders()->where('status', '!=', 'complete')->count() === 0;

        if ($allComplete) {
            $user = $purchaseOrder->customer->user;
            $formattedTime = now()->addDay()->format('Y-m-d H:i'); // أو حسب النظام عندك

            // ✅ تحديث الحالة إلى complete
            $purchaseOrder->status = 'complete';
            $purchaseOrder->delivery_status = 'scheduled';
            $purchaseOrder->delivery_time = $formattedTime;
            $purchaseOrder->save();

            if ($user->userFcmTokens->exists()) {
                try {
                    FirebaseNotification::setTitle('Delivery Appointment Scheduled')
                        ->setBody("A delivery appointment has been scheduled for your order: {$formattedTime}")
                        ->setUsers(collect([$user]))
                        ->setData([
                            'order_id' => $purchaseOrder->id,
                            'delivery_time' => $formattedTime,
                            'type' => 'delivery_scheduled'
                        ])
                        ->push();
                } catch (\Exception $e) {
                    Log::error('Failed to send delivery appointment notification', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }


        return response()->json(['message' => 'Order item marked as complete successfully.']);
    }
}
