<?php

namespace App\Http\Controllers\subManagerController;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Complaint;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DiagramController extends Controller
{
    public function available_count()
    {
        $items = Item::all()->map(function ($item) {
            return [
                'name' => $item->name,
                'available_count' => $item->count - $item->count_reserved,
                'type' => 'item',
            ];
        });

        $rooms = Room::all()->map(function ($room) {
            return [
                'name' => $room->name,
                'available_count' => $room->count - $room->count_reserved,
                'type' => 'room',
            ];
        });

        $result = $items->merge($rooms);

        return response()->json($result);
    }

    public function getInProgressOrders()
    {
        $orders = PurchaseOrder::where('status', 'in_progress')
            ->with([
                'customer.user',
                'roomOrders.room',
                'itemOrders.item',
                'customizationOrders.customization',
                'roomcustomizationOrders.roomCustomization'
            ])
            ->get();

        $result = [];

        foreach ($orders as $po) {
            $customerName = $po->customer ? ($po->customer->user->name ?? $po->customer->name) : null;

            foreach ($po->roomOrders as $ro) {
                $result[] = [
                    'customer_id' => $po->customer_id,
                    'customer_name' => $customerName,
                    'order_name' => $ro->room ? $ro->room->name : null,
                    'type' => 'Room',
                    'status' => $ro->status,
                    'image' => $ro->room ? $ro->room->image_url : null,
                ];
            }

            foreach ($po->itemOrders as $io) {
                $result[] = [
                    'customer_id' => $po->customer_id,
                    'customer_name' => $customerName,
                    'order_name' => $io->item ? $io->item->name : null,
                    'type' => 'Item',
                    'status' => $io->status,
                    'image' => $io->item ? $io->item->image_url : null,
                ];
            }

            foreach ($po->customizationOrders as $co) {
                $result[] = [
                    'customer_id' => $po->customer_id,
                    'customer_name' => $customerName,
                    'order_name' => $co->customization ? $co->customization->name : null,
                    'type' => 'Customization',
                    'status' => $co->status,
                    'image' => $co->customization ? $co->customization->image_url : null,
                ];
            }

            foreach ($po->roomcustomizationOrders as $rco) {
                $result[] = [
                    'customer_id' => $po->customer_id,
                    'customer_name' => $customerName,
                    'order_name' => $rco->roomCustomization ? $rco->roomCustomization->name : null,
                    'type' => 'RoomCustomization',
                    'status' => $rco->status,
                    'image' => $rco->roomCustomization ? $rco->roomCustomization->image_url : null,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'orders' => $result
        ]);
    }

    public function getOrdersStatusPercentages()
    {
        $total = PurchaseOrder::count();

        if ($total === 0) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $statuses = PurchaseOrder::select('status')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $percentages = [];

        foreach ($statuses as $status => $count) {
            $percentages[$status] = round(($count / $total) * 100, 2);
        }

        return response()->json([
            'success' => true,
            'data' => $percentages
        ]);
    }

    public function getTodaysNewData()
    {
        $today = Carbon::today('Asia/Damascus')->setTimezone('Asia/Damascus');
        return $today;


        $orders = PurchaseOrder::whereDate('created_at', $today)->with([
            'customer.user',
            'roomOrders.room',
            'itemOrders.item',
            'customizationOrders.customization',
            'roomcustomizationOrders.roomCustomization'
        ])->get();

        $ordersResult = [];
        foreach ($orders as $po) {
            $customerName = $po->customer ? ($po->customer->user->name ?? $po->customer->name) : null;

            foreach ($po->roomOrders as $ro) {
                $ordersResult[] = [
                    'customer_id' => $po->customer_id,
                    'customer_name' => $customerName,
                    'order_name' => $ro->room ? $ro->room->name : null,
                    'type' => 'Room',
                    'status' => $ro->status,
                    'image' => $ro->room ? $ro->room->image_url : null,
                ];
            }

            foreach ($po->itemOrders as $io) {
                $ordersResult[] = [
                    'customer_id' => $po->customer_id,
                    'customer_name' => $customerName,
                    'order_name' => $io->item ? $io->item->name : null,
                    'type' => 'Item',
                    'status' => $io->status,
                    'image' => $io->item ? $io->item->image_url : null,
                ];
            }

            foreach ($po->customizationOrders as $co) {
                $ordersResult[] = [
                    'customer_id' => $po->customer_id,
                    'customer_name' => $customerName,
                    'order_name' => $co->customization ? $co->customization->name : null,
                    'type' => 'Customization',
                    'status' => $co->status,
                    'image' => $co->customization ? $co->customization->image_url : null,
                ];
            }

            foreach ($po->roomcustomizationOrders as $rco) {
                $ordersResult[] = [
                    'customer_id' => $po->customer_id,
                    'customer_name' => $customerName,
                    'order_name' => $rco->roomCustomization ? $rco->roomCustomization->name : null,
                    'type' => 'RoomCustomization',
                    'status' => $rco->status,
                    'image' => $rco->roomCustomization ? $rco->roomCustomization->image_url : null,
                ];
            }
        }

        $rooms = Room::whereDate('created_at', $today)->get()->map(function ($room) {
            return [
                'room_id' => $room->id,
                'name' => $room->name,
                'category_id' => $room->category_id,
                'image' => $room->image_url,
                'price' => $room->price,
            ];
        });

        $complaints = Complaint::whereDate('created_at', $today)->with('customer.user')->get()->map(function ($c) {
            return [
                'complaint_id' => $c->id,
                'customer_id' => $c->customer_id,
                'customer_name' => $c->customer ? ($c->customer->user->name ?? $c->customer->name) : null,
                'message' => $c->message,
                'status' => $c->status,
            ];
        });

        $branches = Branch::whereDate('created_at', $today)->get()->map(function ($b) {
            return [
                'branch_id' => $b->id,
                'address' => $b->address,
                'latitude' => $b->latitude,
                'longitude' => $b->longitude,
                'sub_manager_id' => $b->sub_manager_id,
            ];
        });

        $users = User::whereDate('created_at', $today)->get()->map(function ($u) {
            return [
                'user_id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ];
        });

        return response()->json([
            'success' => true,
            'orders' => $ordersResult,
            'rooms' => $rooms,
            'complaints' => $complaints,
            'branches' => $branches,
            'users' => $users
        ]);
    }
}