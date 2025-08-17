<?php

namespace App\Http\Controllers\supermanager;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Complaint;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiagramController extends Controller
{

    // public function getBranchCount()
    // {
    //     $totalBranches = Branch::count();

    //     return response()->json([
    //         'total_branches' => $totalBranches
    //     ], 200);
    // }

    // public function getRoomCount()
    // {
    //     $totalRooms = Room::count();

    //     return response()->json([
    //         'total_rooms' => $totalRooms
    //     ], 200);
    // }

    // public function getItemCount()
    // {
    //     $totalItems = Item::count();

    //     return response()->json([
    //         'total_items' => $totalItems
    //     ], 200);
    // }

    // public function getUserCount()
    // {
    //     $totalUsers = User::count();

    //     return response()->json([
    //         'total_users' => $totalUsers
    //     ], 200);
    // }

    // public function getPurchaseOrderCount()
    // {
    //     $totalOrders = PurchaseOrder::count();
    //     return response()->json([
    //         'total_purchase_orders' => $totalOrders
    //     ], 200);
    // }

    // public function getComplaintCount()
    // {
    //     $totalComplaints = Complaint::count();
    //     return response()->json([
    //         'total_complaints' => $totalComplaints
    //     ], 200);
    // }

    // public function calculateProfit()
    // {
    //     $totalCompletedOrders = PurchaseOrder::where('status', 'complete')->sum('total_price');

    //     $profit = $totalCompletedOrders * 0.3;

    //     return response()->json([
    //         'success' => true,
    //         'total_completed_orders_value' => $totalCompletedOrders,
    //         'profit' => $profit
    //     ]);
    // }


    public function getDashboardStats()
    {
        $totalBranches = Branch::count();
        $totalRooms = Room::count();
        $totalItems = Item::count();
        $totalUsers = User::count();
        $totalOrders = PurchaseOrder::count();
        $totalComplaints = Complaint::count();

        // الطلبات المكتملة لحساب الأرباح
        $totalCompletedOrders = PurchaseOrder::where('status', 'complete')->sum('total_price');
        $profit = round($totalCompletedOrders * 0.3, 2);

        return response()->json([
            'success' => true,
            'data' => [
                'total_branches' => $totalBranches,
                'total_rooms' => $totalRooms,
                'total_items' => $totalItems,
                'total_users' => $totalUsers,
                'total_purchase_orders' => $totalOrders,
                'total_complaints' => $totalComplaints,
                'total_completed_orders_value' => $totalCompletedOrders,
                'profit' => $profit
            ]
        ], 200);
    }

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


    // public function allDetails()
    // {
    //     $purchaseOrders = PurchaseOrder::with([
    //         'itemOrders.item',
    //         'roomOrders.room',
    //         'customizationOrders.customization',
    //         'roomcustomizationOrders.roomCustomization'
    //     ])->get();

    //     $data = $purchaseOrders->map(function ($purchaseOrder) {

    //         $items = $purchaseOrder->itemOrders->map(function ($itemOrder) {
    //             return [
    //                 'item_name' => $itemOrder->item->name ?? 'Unknown',
    //                 'quantity' => $itemOrder->count,
    //                 'unit_price' => $itemOrder->price,
    //                 'total_price' => $itemOrder->price * $itemOrder->count,
    //             ];
    //         });

    //         $rooms = $purchaseOrder->roomOrders->map(function ($roomOrder) {
    //             return [
    //                 'room_name' => $roomOrder->room->name ?? 'Unknown',
    //                 'quantity' => $roomOrder->count,
    //                 'deposite_price' => $roomOrder->deposite_price,
    //                 'total_price' => $roomOrder->deposite_price * $roomOrder->count,
    //             ];
    //         });

    //         $customizations = $purchaseOrder->customizationOrders->map(function ($customOrder) {
    //             return [
    //                 'customization_name' => $customOrder->customization->name ?? 'Unknown',
    //                 'quantity' => $customOrder->count,
    //                 'deposite_price' => $customOrder->deposite_price,
    //                 'total_price' => $customOrder->deposite_price * $customOrder->count,
    //             ];
    //         });

    //         $roomCustomizations = $purchaseOrder->roomcustomizationOrders->map(function ($roomCustOrder) {
    //             return [
    //                 'room_customization_name' => $roomCustOrder->roomCustomization->name ?? 'Unknown',
    //                 'quantity' => $roomCustOrder->count,
    //                 'deposite_price' => $roomCustOrder->deposite_price,
    //                 'total_price' => $roomCustOrder->deposite_price * $roomCustOrder->count,
    //             ];
    //         });

    //         return [
    //             'purchase_order_id' => $purchaseOrder->id,
    //             'sale_date' => $purchaseOrder->created_at->format('Y-m-d H:i:s'), // ✅ إضافة تاريخ البيع
    //             'items' => $items,
    //             'rooms' => $rooms,
    //             'customizations' => $customizations,
    //             'room_customizations' => $roomCustomizations,
    //             'total_order_price' =>
    //             $items->sum('total_price') +
    //                 $rooms->sum('total_price') +
    //                 $customizations->sum('total_price') +
    //                 $roomCustomizations->sum('total_price'),
    //         ];
    //     });

    //     return response()->json($data);
    // }
    public function sales_details()
    {
        $purchaseOrders = PurchaseOrder::with([
            'itemOrders.item',
            'roomOrders.room',
            'customizationOrders.customization',
            'roomcustomizationOrders.roomCustomization'
        ])->get();

        $data = $purchaseOrders->flatMap(function ($purchaseOrder) {
            $saleDate = $purchaseOrder->created_at->format('Y-m-d H:i:s');

            $items = $purchaseOrder->itemOrders->map(function ($itemOrder) use ($purchaseOrder, $saleDate) {
                return [
                    'id' => $purchaseOrder->id,
                    'name' => $itemOrder->item->name ?? 'Unknown',
                    'quantity' => $itemOrder->count,
                    'unit_price' => $itemOrder->price,
                    'total_price' => $itemOrder->price * $itemOrder->count,
                    'sale_date' => $saleDate,
                    'type' => 'item',
                ];
            });

            // الغرف
            $rooms = $purchaseOrder->roomOrders->map(function ($roomOrder) use ($purchaseOrder, $saleDate) {
                return [
                    'id' => $purchaseOrder->id,
                    'name' => $roomOrder->room->name ?? 'Unknown',
                    'quantity' => $roomOrder->count,
                    'unit_price' => $roomOrder->deposite_price,
                    'total_price' => $roomOrder->deposite_price * $roomOrder->count,
                    'sale_date' => $saleDate,
                    'type' => 'room',
                ];
            });

            // تخصيص العناصر
            $customizations = $purchaseOrder->customizationOrders->map(function ($customOrder) use ($purchaseOrder, $saleDate) {
                return [
                    'id' => $purchaseOrder->id,
                    'name' => $customOrder->customization->name ?? 'Unknown',
                    'quantity' => $customOrder->count,
                    'unit_price' => $customOrder->deposite_price,
                    'total_price' => $customOrder->deposite_price * $customOrder->count,
                    'sale_date' => $saleDate,
                    'type' => 'customization',
                ];
            });

            // تخصيص الغرف
            $roomCustomizations = $purchaseOrder->roomcustomizationOrders->map(function ($roomCustOrder) use ($purchaseOrder, $saleDate) {
                return [
                    'id' => $purchaseOrder->id,
                    'name' => $roomCustOrder->roomCustomization->name ?? 'Unknown',
                    'quantity' => $roomCustOrder->count,
                    'unit_price' => $roomCustOrder->deposite_price,
                    'total_price' => $roomCustOrder->deposite_price * $roomCustOrder->count,
                    'sale_date' => $saleDate,
                    'type' => 'room_customization',
                ];
            });

            return $items
                ->concat($rooms)
                ->concat($customizations)
                ->concat($roomCustomizations);
        });

        return response()->json($data);
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

    // public function calculateMonthlyProfit()
    // {
    //     $monthlyProfits = PurchaseOrder::select(
    //         DB::raw('YEAR(created_at) as year'),
    //         DB::raw('MONTH(created_at) as month'),
    //         DB::raw('SUM(total_price) as total_completed_orders_value')
    //     )
    //         ->where('status', 'complete')
    //         ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
    //         ->orderBy('year', 'asc')
    //         ->orderBy('month', 'asc')
    //         ->get()
    //         ->map(function ($row) {
    //             return [
    //                 'year' => $row->year,
    //                 'month' => $row->month,
    //                 'total_completed_orders_value' => (float) $row->total_completed_orders_value,
    //                 'profit' => round($row->total_completed_orders_value * 0.3, 2)
    //             ];
    //         });

    //     return response()->json([
    //         'success' => true,
    //         'data' => $monthlyProfits
    //     ], 200);
    // }

    public function calculateMonthlyProfit()
    {
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;

        $profits = PurchaseOrder::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(total_price) as total_completed_orders_value')
        )
            ->where('status', 'complete')
            ->whereYear('created_at', $currentYear)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->pluck('total_completed_orders_value', 'month');

        $monthlyProfits = [];
        for ($m = 1; $m <= 12; $m++) {
            if ($m > $currentMonth) {
                $monthlyProfits[] = [
                    'year' => $currentYear,
                    'month' => $m,
                    'profit' => null
                ];
            } else {
                $value = $profits[$m] ?? null;
                $monthlyProfits[] = [
                    'year' => $currentYear,
                    'month' => $m,
                    'profit' => $value ? round($value * 0.3, 2) : null
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $monthlyProfits
        ]);
    }

    public function getTodaysNewData()
    {
        $today = Carbon::today();

        // الطلبات الجديدة
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

        // الغرف الجديدة
        $rooms = Room::whereDate('created_at', $today)->get()->map(function ($room) {
            return [
                'room_id' => $room->id,
                'name' => $room->name,
                'category_id' => $room->category_id,
                'image' => $room->image_url,
                'price' => $room->price,
            ];
        });

        // الشكاوي الجديدة
        $complaints = Complaint::whereDate('created_at', $today)->with('customer.user')->get()->map(function ($c) {
            return [
                'complaint_id' => $c->id,
                'customer_id' => $c->customer_id,
                'customer_name' => $c->customer ? ($c->customer->user->name ?? $c->customer->name) : null,
                'message' => $c->message,
                'status' => $c->status,
            ];
        });

        // الفروع الجديدة
        $branches = Branch::whereDate('created_at', $today)->get()->map(function ($b) {
            return [
                'branch_id' => $b->id,
                'address' => $b->address,
                'latitude' => $b->latitude,
                'longitude' => $b->longitude,
                'sub_manager_id' => $b->sub_manager_id,
            ];
        });

        // المستخدمين الجدد
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


    public function getInProgressOrders()
    {
        // جلب جميع الطلبات التي الحالة الخاصة بها in_progress
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
}
