<?php

namespace App\Http\Controllers\subManagerController;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getCustomers()
    {
        $customers = Customer::with('user:id,name,email')
            ->get(['id', 'user_id', 'profile_image']);

        $data = $customers->map(function ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->user->name ?? null,
                'email' => $customer->user->email ?? null,
                'profile_image' => $customer->profile_image,
            ];
        });
        return response()->json([
            'success' => true,
            'customers' => $data
        ], 200);
    }
    public function getCustomerOrders($customer_id)
    {
        $customer = Customer::with([
            'purchaseOrders.roomOrders.room',
            'purchaseOrders.itemOrders.item',
            'purchaseOrders.customizationOrders.customization',
            'purchaseOrders.roomcustomizationOrders.roomCustomization'
        ])->find($customer_id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        $orders = [];

        foreach ($customer->purchaseOrders as $po) {
            foreach ($po->roomOrders as $ro) {
                $orders[] = [
                    'name' => $ro->room ? $ro->room->name : null,
                    'type' => 'Room',
                    'status' => $ro->status,
                    'image' => $ro->room ? $ro->room->image_url : null,
                ];
            }

            foreach ($po->itemOrders as $io) {
                $orders[] = [
                    'name' => $io->item ? $io->item->name : null,
                    'type' => 'Item',
                    'status' => $io->status,
                    'image' => $io->item ? $io->item->image_url : null,
                ];
            }

            foreach ($po->customizationOrders as $co) {
                $orders[] = [
                    'name' => $co->customization ? $co->customization->name : null,
                    'type' => 'Customization',
                    'status' => $co->status,
                    'image' => $co->customization ? $co->customization->image_url : null,
                ];
            }

            // foreach ($po->roomcustomizationOrders as $rco) {
            //     $orders[] = [
            //         'name' => $rco->roomCustomization ? $rco->roomCustomization->name : null,
            //         'type' => 'RoomCustomization',
            //         'status' => $rco->status,
            //         'image' => $rco->roomCustomization ? $rco->roomCustomization->image_url : null,
            //     ];
            // }
        }

        return response()->json([
            'success' => true,
            'customer_id' => $customer->id,
            'customer_name' => $customer->user ? $customer->user->name : null,
            // 'orders' => $orders
        ]);
    }
}