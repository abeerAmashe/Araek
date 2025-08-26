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

            if ($order->item && $order->item->count() > 0) {
                $previewImage = $order->item[0]->image_url ?? null;
            }

            if (!$previewImage && $order->roomOrders && $order->roomOrders->count() > 0) {
                $firstRoom = $order->roomOrders[0]->room ?? null;
                $previewImage = $firstRoom->image_url ?? null;
            }

            $now = \Carbon\Carbon::now();
            $receiveDate = \Carbon\Carbon::parse($order->recive_date)->endOfDay();
            $remainingTime = $now->diffForHumans($receiveDate, [
                'parts' => 2,
                'short' => true,
                'syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW
            ]);

            $items = $order->item->map(function ($item) {
                return [
                    'item_id' => $item->id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'count' => $item->pivot->count,
                    'image_url' => $item->image_url,
                ];
            });

            $rooms = $order->roomOrders->map(function ($roomOrder) {
                $room = $roomOrder->room;
                return [
                    'room_id' => $room->id ?? null,
                    'name' => $room->name ?? null,
                    'price' => $room->price ?? null,
                    'count' => $roomOrder->count,
                    'image_url' => $room->image_url ?? null,
                ];
            });
            $customizations = $order->customizationOrders->map(function ($customOrder) {
                $customization = $customOrder->customization;
                return [
                    'customization_id' => $customization->id ?? null,
                    'name' => $customization->item->name ?? null,
                    'price' => $customization->final_price ?? null,
                    'count' => $customOrder->count,
                ];
            });

            $roomCustomizations = $order->roomcustomizationOrders->map(function ($roomCustomOrder) {
                return [
                    'room_customization_id' => $roomCustomOrder->id ?? null,
                    'name' => $roomCustomOrder->roomCustomization->room->name ?? null,
                    'price' => $roomCustomOrder->deposite_price ?? null,
                    'count' => $roomCustomOrder->count,
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
                'item_customizations' => $customizations,
                'room_customizations' => $roomCustomizations,
            ];
        });

        return response()->json([
            'orders' => $orders,
        ]);
    }

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
                    'remaining_time' =>(float) $remainingTime,
                    'remaining_bill' => (float) $remainingAmount,
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

        $remainingTime = 'less than an hour';
        if ($order->recive_date) {
            $reciveDate = \Carbon\Carbon::parse($order->recive_date)->endOfDay();
            $remainingTime = \Carbon\Carbon::now()->diffForHumans($reciveDate, [
                'parts' => 2,
                'short' => true,
                'syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW,
            ]);
        }


        $previewImage = null;
        if ($order->item && $order->item->count() > 0) {
            $previewImage = $order->item[0]->image_url ?? null;
        } elseif ($order->roomOrders && $order->roomOrders->count() > 0) {
            $previewImage = $order->roomOrders[0]->room->image_url ?? null;
        }

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