<?php

namespace App\Helpers;

use App\Models\Cart;
use App\Models\Item;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CartHelper
{
    public static function validateCartReservations()
    {
        $expiredCarts = Cart::where('reserved_at', '<', Carbon::now()->subHours(24))->get();

        foreach ($expiredCarts as $cart) {
            // ==== الحالة: السلة تحتوي عنصر مباشر ====
            if ($cart->item_id) {
                $item = Item::find($cart->item_id);
                if ($item) {
                    // حذف الحجز القديم
                    $item->count_reserved = max(0, $item->count_reserved - $cart->count_reserved);
                    $item->save();

                    // حساب المتوفر
                    $availableCount = max(0, $item->count - $item->count_reserved);
                    $newReservedCount = min($cart->count, $availableCount);
                    $missingCount = $cart->count - $newReservedCount;
                    $timeForMissing = $missingCount * $item->time;

                    // تحديث السلة
                    $cart->count_reserved = $newReservedCount;
                    $cart->time = $timeForMissing;
                    $cart->reserved_at = Carbon::now();
                    $cart->save();

                    // إعادة الحجز في العنصر
                    $item->count_reserved += $newReservedCount;
                    if ($item->count_reserved > $item->count) {
                        $item->count_reserved = $item->count;
                    }
                    $item->save();
                }
            }

            // ==== الحالة: السلة تحتوي غرفة ====
            elseif ($cart->room_id) {
                $room = Room::with('items')->find($cart->room_id);
                if ($room) {
                    // حذف الحجز القديم من الغرفة
                    $room->count_reserved = max(0, $room->count_reserved - $cart->count_reserved);
                    $room->save();

                    // حذف الحجز القديم من العناصر التابعة للغرفة
                    foreach ($room->items as $roomItem) {
                        $roomItem->count_reserved = max(0, $roomItem->count_reserved - $cart->count_reserved);
                        $roomItem->save();
                    }

                    // إعادة حساب العدد القابل للحجز الآن
                    $availableRooms = max(0, $room->count - $room->count_reserved);
                    $maxReservable = min($cart->count, $availableRooms);
                    $missingTime = 0;

                    // تحقق من توفر كل عنصر تابع للغرفة
                    foreach ($room->items as $roomItem) {
                        $availableItem = max(0, $roomItem->count - $roomItem->count_reserved);
                        if ($availableItem < $maxReservable) {
                            $reduction = $maxReservable - $availableItem;
                            $maxReservable = $availableItem;
                            $missingItemCount = $cart->count - $maxReservable;
                            $missingTime += $missingItemCount * $roomItem->time;
                        }
                    }

                    // وقت الغرف الناقصة
                    $missingRoomCount = $cart->count - $maxReservable;
                    $missingTime += $missingRoomCount * $room->time;

                    // تحديث السلة
                    $cart->count_reserved = $maxReservable;
                    $cart->time = $missingTime;
                    $cart->reserved_at = Carbon::now();
                    $cart->save();

                    // إعادة الحجز في الغرفة
                    $room->count_reserved += $maxReservable;
                    if ($room->count_reserved > $room->count) {
                        $room->count_reserved = $room->count;
                    }
                    $room->save();

                    // إعادة الحجز في العناصر التابعة
                    foreach ($room->items as $roomItem) {
                        $roomItem->count_reserved += $maxReservable;
                        if ($roomItem->count_reserved > $roomItem->count) {
                            $roomItem->count_reserved = $roomItem->count;
                        }
                        $roomItem->save();
                    }
                }
            }
        }

        Log::info('Expired reservations checked and updated!');
    }
}