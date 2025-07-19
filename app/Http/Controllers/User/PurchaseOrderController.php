<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemOrder;
use App\Models\PurchaseOrder;
use App\Models\Room;
use App\Models\RoomOrder;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function getOrdersByCustomer()
    {
        $customerId = auth()->user()->customer->id;

        $customer = Customer::with([
            'purchaseOrders.item',
            'purchaseOrders.roomOrders.room'
        ])->find($customerId);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $orders = $customer->purchaseOrders->map(function ($order) {
            $previewImage = null;

            // صورة أول عنصر إن وجد
            if ($order->item && $order->item->count() > 0) {
                $previewImage = $order->item[0]->image_url ?? null;
            }

            // إذا ما في صورة من العناصر، نبحث عن أول صورة من الغرف
            if (!$previewImage && $order->roomOrders && $order->roomOrders->count() > 0) {
                $firstRoom = $order->roomOrders[0]->room ?? null;
                $previewImage = $firstRoom->image_url ?? null;
            }

            // الوقت المتبقي
            $now = \Carbon\Carbon::now();
            $receiveDate = \Carbon\Carbon::parse($order->recive_date)->endOfDay();
            $remainingTime = $now->diffForHumans($receiveDate, [
                'parts' => 2,
                'short' => true,
                'syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW
            ]);

            // العناصر
            $items = $order->item->map(function ($item) {
                return [
                    'item_id' => $item->id,
                    'room_id' => $item->room_id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'count' => $item->pivot->count,
                    'image_url' => $item->image_url,
                ];
            });

            // الغرف
            $rooms = $order->roomOrders->map(function ($roomOrder) {
                $room = $roomOrder->room;
                return [
                    'item_id' => null,
                    'room_id' => $room->id ?? null,
                    'name' => $room->name ?? null,
                    'price' => $room->price ?? null,
                    'count' => $roomOrder->count,
                    'image_url' => $room->image_url ?? null,
                ];
            });

            return [
                'id' => $order->id,
                'preview_image' => $previewImage,
                'total_price' => $order->total_price,
                'price_after_rabbon' => $order->price_after_rabbon,
                'remaining_time' => $remainingTime,
                'status' => $order->status,
                'items' => $items,
                'rooms' => $rooms,
            ];
        });

        return response()->json([
            'orders' => $orders,
        ]);
    }

    public function getAllOrders()
    {
        $orders = PurchaseOrder::select('id', 'status', 'recive_date', 'price_after_rabbon', 'is_paid')
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
                    'remaining_time' => $remainingTime,
                    'remaining_bill' => number_format($remainingAmount, 2) . ' $',
                    'is_paid' => $order->is_paid ? 'Paid in full' : 'Not paid in full',
                ];
            });

        return response()->json([
            'message' => 'Orders retrieved successfully.',
            'orders' => $orders,
        ]);
    }

    public function getOrderDetails($orderId)
    {
        $order = PurchaseOrder::with(['roomOrders.room', 'item'])->find($orderId);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found.',
            ], 404);
        }

        // حساب الوقت المتبقي
        $remainingTime = 'less than an hour';
        if ($order->recive_date) {
            $reciveDate = \Carbon\Carbon::parse($order->recive_date)->endOfDay();
            $remainingTime = \Carbon\Carbon::now()->diffForHumans($reciveDate, [
                'parts' => 2,
                'short' => true,
                'syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW,
            ]);
        }


        // تحديد أول صورة للعرض
        $previewImage = null;
        if ($order->item && $order->item->count() > 0) {
            $previewImage = $order->item[0]->image_url ?? null;
        } elseif ($order->roomOrders && $order->roomOrders->count() > 0) {
            $previewImage = $order->roomOrders[0]->room->image_url ?? null;
        }

        // العناصر المرتبطة
        $items = $order->item->map(function ($item) {
            return [
                'item_id' => $item->id,
                'room_id' => $item->room_id,
                'name' => $item->name,
                'price' => $item->price,
                'count' => $item->pivot->count ?? 0,
                'image_url' => $item->image_url,
            ];
        });

        // الغرف المرتبطة
        $rooms = $order->roomOrders->map(function ($roomOrder) {
            $room = $roomOrder->room;
            return [
                'item_id' => null,
                'room_id' => $room->id ?? null,
                'name' => $room->name ?? null,
                'price' => $room->price ?? null,
                'count' => $roomOrder->count ?? 0,
                'image_url' => $room->image_url ?? null,
            ];
        });

        return response()->json([
            'orders' => [
                [
                    'id' => $order->id,
                    'preview_image' => $previewImage,
                    'total_price' => $order->total_price,
                    'price_after_rabbon' => $order->price_after_rabbon,
                    'remaining_time' => $remainingTime,
                    'status' => $order->status,
                    'items' => $items,
                    'rooms' => $rooms,
                ]
            ]
        ]);
    }

    public function cancelOrder($orderId)
    {
        $order = PurchaseOrder::find($orderId);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        if ($order->status === 'completed') {
            return response()->json(['error' => 'Cannot cancel a completed order.'], 403);
        }

        try {
            DB::beginTransaction();

            $itemOrders = ItemOrder::where('purchase_order_id', $orderId)->get();
            foreach ($itemOrders as $itemOrder) {
                $item = Item::find($itemOrder->item_id);
                if ($item) {
                    $reservedCount = $itemOrder->count_reserved;
                    $item->count_reserved -= $reservedCount;
                    $item->save();
                }
            }

            $roomOrders = RoomOrder::where('purchase_order_id', $orderId)->get();
            foreach ($roomOrders as $roomOrder) {
                $room = Room::find($roomOrder->room_id);
                if ($room) {
                    $reservedCount = $roomOrder->count_reserved;
                    $room->count_reserved -= $reservedCount;
                    $room->save();
                }
            }

            $order->status = 'cancelled';
            $order->save();

            DB::commit();

            return response()->json(['message' => 'Order cancelled successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to cancel order.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}