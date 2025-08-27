<?php

namespace App\Http\Controllers\workshopmanager;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Room;
use BilalMardini\FirebaseNotification\Facades\FirebaseNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class tempcontroller extends Controller
{
    public function markOrderAsComplete($orderId)
    {
        $order = PurchaseOrder::with('customer.user.userFcmTokens')->find($orderId);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        $order->update([
            'status' => 'complete'
        ]);

        $user = $order->customer->user ?? null;

        if ($user && $user->userFcmTokens->count() > 0) {
            try {
                FirebaseNotification::setTitle('Your order is complete 🎉')
                    ->setBody("Your order #{$order->id} has been completed")
                    ->setUsers(collect([$user]))
                    ->setData([
                        'order_id' => $order->id,
                        'status'   => 'complete',
                        'type'     => 'order_completed'
                    ])
                    ->push();
            } catch (\Exception $e) {
                Log::error('Notification failed', [
                    'order_id' => $order->id,
                    'user_id'  => $user->id ?? null,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Order status updated to complete',
            'data' => $order
        ]);
    }

    public function showZeroPriceAndTime()
    {
        $rooms = Room::where('price', 0)
            ->where('time', 0)
            ->get();

        $items = Item::where('price', 0)
            ->where('time', 0)
            ->get();

        return response()->json([
            'rooms' => $rooms,
            'items' => $items,
        ]);
    }

    public function updatePriceAndTime(Request $request, $type, $id)
{
    if ($type === 'room') {
        $model = Room::find($id);
    } elseif ($type === 'item') {
        $model = Item::find($id);
    } else {
        return response()->json(['message' => 'Invalid type'], 400);
    }

    if (!$model) {
        return response()->json(['message' => ucfirst($type) . ' not found'], 404);
    }

    $data = $request->validate([
        'price' => 'required|numeric|min:0',
        'time'  => 'required|numeric|min:0',
    ]);

    $priceWithProfit = $data['price'] * 1.3;

    $model->update([
        'price' => $priceWithProfit,
        'time'  => $data['time'],
    ]);

    return response()->json([
        'message' => ucfirst($type) . ' updated successfully',
        $type => $model
    ]);
}


    public function updateItemCount(Request $request, $itemId)
    {
        $request->validate([
            'count' => 'required|integer|min:0'
        ]);

        $item = Item::find($itemId);

        if (!$item) {
            return response()->json([
                'message' => 'Item not found'
            ], 404);
        }

        $item->count = $request->count;
        $item->save();

        return response()->json([
            'message' => 'Item count updated successfully',
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'count' => $item->count
            ]
        ], 200);
    }

    public function updateRoomCount(Request $request, $roomId)
    {
        $request->validate([
            'count' => 'required|integer|min:0'
        ]);

        $room = Room::find($roomId);

        if (!$room) {
            return response()->json([
                'message' => 'Room not found'
            ], 404);
        }

        $room->count = $request->count;
        $room->save();

        return response()->json([
            'message' => 'Room count updated successfully',
            'room' => [
                'id' => $room->id,
                'name' => $room->name,
                'count' => $room->count
            ]
        ], 200);
    }
}