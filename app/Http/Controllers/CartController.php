<?php

namespace App\Http\Controllers;

use App\Models\AvailableTime;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\CartItemReservation;
use App\Models\DeliveryCompanyAvailability;
use App\Models\GallaryManager;
use App\Models\Item;
use App\Models\ItemOrder;
use App\Models\PlaceCost;
use App\Models\PurchaseOrder;
use App\Models\Room;
use App\Models\WorkshopManagerRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CartController extends Controller
{

    // public function addToCart2(Request $request)
    // {
    //     $user = auth()->user();
    //     $customerId = $user->customer->id;

    //     $requiredFields = ['item_id', 'room_id', 'customization_id', 'room_customization_id'];
    //     $isValid = false;
    //     foreach ($requiredFields as $field) {
    //         if ($request->has($field)) {
    //             $isValid = true;
    //             break;
    //         }
    //     }

    //     if (!$isValid) {
    //         return response()->json(['message' => 'Invalid request. Missing one of item_id, room_id, customization_id, or room_customization_id'], 200);
    //     }

    //     $itemId = $request->input('item_id');
    //     $roomId = $request->input('room_id');
    //     $customizationId = $request->input('customization_id');
    //     $roomCustomizationId = $request->input('room_customization_id');
    //     $count = (int) $request->input('count', 1);

    //     if ($count <= 0) {
    //         return response()->json(['message' => 'Count must be greater than 0'], 200);
    //     }

    //     $pricePerItem = 0.00;
    //     $timePerItem = 0.00;
    //     $reservedNow = 0;
    //     $partialTime = 0;

    //     $cartQuery = Cart::where('customer_id', $customerId);

    //     if ($itemId) {
    //         $item = Item::find($itemId);
    //         if (!$item) return response()->json(['message' => 'Item not found'], 200);

    //         $availableCount = $item->count - $item->count_reserved;
    //         $reservedNow = min($count, $availableCount);
    //         $missingCount = $count - $reservedNow;
    //         $partialTime = $missingCount * $item->time;

    //         $pricePerItem = (float) $item->price;
    //         $timePerItem = (float) $item->time;

    //         if ($reservedNow > 0) {
    //             $item->count_reserved += $reservedNow;
    //             $item->save();
    //         }

    //         $cartQuery->where('item_id', $itemId)
    //             ->whereNull('room_id')
    //             ->whereNull('customization_id')
    //             ->whereNull('room_customization_id');

    //         $cart = $cartQuery->first();

    //         if ($cart) {
    //             $cart->count += $count;
    //             $cart->count_reserved += $reservedNow;
    //             $cart->price = $pricePerItem * $cart->count;
    //             $cart->time = ($cart->count - $cart->count_reserved) * $timePerItem;
    //             $cart->price_per_item = $pricePerItem;
    //             $cart->time_per_item = $timePerItem;
    //             $cart->available_count_at_addition = $availableCount;
    //             $cart->reserved_at = now();
    //             $cart->save();
    //         } else {
    //             $cart = Cart::create([
    //                 'customer_id' => $customerId,
    //                 'item_id' => $itemId,
    //                 'count' => $count,
    //                 'count_reserved' => $reservedNow,
    //                 'time_per_item' => $timePerItem,
    //                 'price_per_item' => $pricePerItem,
    //                 'time' => $partialTime,
    //                 'price' => $pricePerItem * $count,
    //                 'available_count_at_addition' => $availableCount,
    //                 'reserved_at' => now(),
    //             ]);
    //         }
    //     } elseif ($roomId) {
    //         $room = Room::with('items')->find($roomId);
    //         if (!$room) return response()->json(['message' => 'Room not found'], 200);

    //         $availableCount = $room->count - $room->count_reserved;
    //         $reservedNow = min($count, $availableCount);
    //         $missingCount = $count - $reservedNow;
    //         $partialTime = $missingCount * $room->time;

    //         $pricePerItem = (float) $room->price;
    //         $timePerItem = (float) $room->time;

    //         // إنشاء أو تحديث الـ cart الخاص بالغرفة
    //         $cart = Cart::firstOrCreate(
    //             [
    //                 'customer_id' => $customerId,
    //                 'room_id' => $roomId,
    //             ],
    //             [
    //                 'count' => 0,
    //                 'count_reserved' => 0,
    //                 'time_per_item' => $timePerItem,
    //                 'price_per_item' => $pricePerItem,
    //                 'time' => 0,
    //                 'price' => 0,
    //                 'available_count_at_addition' => $availableCount,
    //                 'reserved_at' => now(),
    //             ]
    //         );

    //         if ($reservedNow > 0) {
    //             $room->count_reserved += $reservedNow;
    //             $room->save();

    //             foreach ($room->items as $roomItem) {
    //                 $availableItemCount = $roomItem->count - $roomItem->count_reserved;
    //                 $reserveCountForItem = min($reservedNow, $availableItemCount);

    //                 if ($reserveCountForItem > 0) {
    //                     $roomItem->count_reserved += $reserveCountForItem;
    //                     $roomItem->save();

    //                     $reservation = CartItemReservation::where('cart_id', $cart->id)
    //                         ->where('item_id', $roomItem->id)
    //                         ->first();

    //                     if (!$reservation) {
    //                         $reservation = CartItemReservation::create([
    //                             'cart_id' => $cart->id,
    //                             'item_id' => $roomItem->id,
    //                             'count_reserved' => $reserveCountForItem,
    //                         ]);
    //                     } else {
    //                         $reservation->count_reserved += $reserveCountForItem;
    //                         $reservation->save();
    //                     }
    //                 }
    //             }
    //         }

    //         // تحديث بيانات السلة بعد الإضافة
    //         $cart->count += $count;
    //         $cart->count_reserved += $reservedNow;
    //         $cart->price = $pricePerItem * $cart->count;
    //         $cart->time = ($cart->count - $cart->count_reserved) * $timePerItem;
    //         $cart->price_per_item = $pricePerItem;
    //         $cart->time_per_item = $timePerItem;
    //         $cart->available_count_at_addition = $availableCount;
    //         $cart->reserved_at = now();
    //         $cart->save();
    //     }

    //     $cartItems = Cart::where('customer_id', $customerId)->get();
    //     $totalCartPrice = $cartItems->sum('price');
    //     $totalCartTime = $cartItems->sum('time');
    //     $depositAmount = $totalCartPrice * 0.5;

    //     return response()->json([
    //         'message' => 'Added/Updated successfully in cart',
    //         'cart' => $cart,
    //         'total_time' => $totalCartTime,
    //         'total_price' => $totalCartPrice,
    //         'deposit' => $depositAmount,
    //         'item_time' => $timePerItem,
    //         'item_price' => $pricePerItem * $count,
    //     ]);
    // }

    public function addToCart2(Request $request)
    {
        $user = auth()->user();
        $customerId = $user->customer->id;

        $requiredFields = ['item_id', 'room_id', 'customization_id', 'room_customization_id'];
        $isValid = false;
        foreach ($requiredFields as $field) {
            if ($request->has($field)) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            return response()->json(['message' => 'Invalid request. Missing one of item_id, room_id, customization_id, or room_customization_id'], 200);
        }

        $itemId = $request->input('item_id');
        $roomId = $request->input('room_id');
        $customizationId = $request->input('customization_id');
        $roomCustomizationId = $request->input('room_customization_id');
        $count = (int) $request->input('count', 1);

        if ($count <= 0) {
            return response()->json(['message' => 'Count must be greater than 0'], 200);
        }

        $pricePerItem = 0.00;
        $timePerItem = 0.00;
        $reservedNow = 0;
        $partialTime = 0;

        $cartQuery = Cart::where('customer_id', $customerId);

        if ($itemId) {
            $item = Item::find($itemId);
            if (!$item) return response()->json(['message' => 'Item not found'], 200);

            $availableCount = max(0, $item->count - $item->count_reserved);
            $reservedNow = min($count, $availableCount);
            $missingCount = $count - $reservedNow;
            $partialTime = $missingCount * $item->time;

            $pricePerItem = (float) $item->price;
            $timePerItem = (float) $item->time;

            if ($reservedNow > 0) {
                $item->count_reserved += $reservedNow;
                if ($item->count_reserved > $item->count) {
                    $item->count_reserved = $item->count;
                }
                $item->save();
            }

            $cartQuery->where('item_id', $itemId)
                ->whereNull('room_id')
                ->whereNull('customization_id')
                ->whereNull('room_customization_id');

            $cart = $cartQuery->first();

            if ($cart) {
                $cart->count += $count;
                $cart->count_reserved += $reservedNow;
                if ($cart->count_reserved > $cart->count) {
                    $cart->count_reserved = $cart->count;
                }
                $cart->price = $pricePerItem * $cart->count;
                $cart->time = ($cart->count - $cart->count_reserved) * $timePerItem;
                $cart->price_per_item = $pricePerItem;
                $cart->time_per_item = $timePerItem;
                $cart->available_count_at_addition = $availableCount;
                $cart->reserved_at = now();
                $cart->save();
            } else {
                $cart = Cart::create([
                    'customer_id' => $customerId,
                    'item_id' => $itemId,
                    'count' => $count,
                    'count_reserved' => $reservedNow,
                    'time_per_item' => $timePerItem,
                    'price_per_item' => $pricePerItem,
                    'time' => $partialTime,
                    'price' => $pricePerItem * $count,
                    'available_count_at_addition' => $availableCount,
                    'reserved_at' => now(),
                ]);
            }
        } elseif ($roomId) {
            $room = Room::with('items')->find($roomId);
            if (!$room) return response()->json(['message' => 'Room not found'], 200);

            $availableCount = max(0, $room->count - $room->count_reserved);
            $reservedNow = min($count, $availableCount);
            $missingCount = $count - $reservedNow;
            $partialTime = $missingCount * $room->time;

            $pricePerItem = (float) $room->price;
            $timePerItem = (float) $room->time;

            $cart = Cart::firstOrCreate(
                [
                    'customer_id' => $customerId,
                    'room_id' => $roomId,
                ],
                [
                    'count' => 0,
                    'count_reserved' => 0,
                    'time_per_item' => $timePerItem,
                    'price_per_item' => $pricePerItem,
                    'time' => 0,
                    'price' => 0,
                    'available_count_at_addition' => $availableCount,
                    'reserved_at' => now(),
                ]
            );

            if ($reservedNow > 0) {
                $room->count_reserved += $reservedNow;
                if ($room->count_reserved > $room->count) {
                    $room->count_reserved = $room->count;
                }
                $room->save();

                foreach ($room->items as $roomItem) {
                    $availableItemCount = max(0, $roomItem->count - $roomItem->count_reserved);
                    $reserveCountForItem = min($reservedNow, $availableItemCount);

                    if ($reserveCountForItem > 0) {
                        $roomItem->count_reserved += $reserveCountForItem;
                        if ($roomItem->count_reserved > $roomItem->count) {
                            $roomItem->count_reserved = $roomItem->count;
                        }
                        $roomItem->save();

                        $reservation = CartItemReservation::where('cart_id', $cart->id)
                            ->where('item_id', $roomItem->id)
                            ->where('room_id', $room->id)
                            ->first();

                        if (!$reservation) {
                            $reservation = CartItemReservation::create([
                                'cart_id' => $cart->id,
                                'item_id' => $roomItem->id,
                                'room_id' => $room->id,
                                'count_reserved' => $reserveCountForItem,
                            ]);
                        } else {
                            $reservation->count_reserved += $reserveCountForItem;
                            $reservation->save();
                        }
                    }

                    $missingItemCount = max(0, $reservedNow - $reserveCountForItem);
                    if ($missingItemCount > 0) {
                        $partialTime += $missingItemCount * $roomItem->time;
                    }
                }
            }

            $cart->count += $count;
            $cart->count_reserved += $reservedNow;
            if ($cart->count_reserved > $cart->count) {
                $cart->count_reserved = $cart->count;
            }
            $cart->price = $pricePerItem * $cart->count;
            $cart->time = $partialTime;
            $cart->price_per_item = $pricePerItem;
            $cart->time_per_item = $timePerItem;
            $cart->available_count_at_addition = $availableCount;
            $cart->reserved_at = now();
            $cart->save();
        }

        $cartItems = Cart::where('customer_id', $customerId)->get();
        $totalCartPrice = $cartItems->sum('price');
        $totalCartTime = $cartItems->sum('time');
        $depositAmount = $totalCartPrice * 0.5;

        return response()->json([
            'message' => 'Added/Updated successfully in cart',
            'cart' => $cart,
            'total_time' => $totalCartTime,
            'total_price' => $totalCartPrice,
            'deposit' => $depositAmount,
            'item_time' => $timePerItem,
            'item_price' => $pricePerItem * $count,
        ]);
    }

    public function getCartDetails()
    {
        $customerId = auth()->user()->customer->id;

        $cartItems = \App\Models\Cart::with(['item', 'room'])
            ->where('customer_id', $customerId)
            ->get();

        $rooms = [];
        $items = [];
        $totalPrice = 0;
        $totalTime = 0;

        foreach ($cartItems as $cart) {
            $lineTotalPrice = (float) $cart->price;
            $lineTotalTime = (float) $cart->time;

            $totalPrice += $lineTotalPrice;
            $totalTime += $lineTotalTime;

            if ($cart->room) {
                $rooms[] = [
                    'id' => $cart->room->id,
                    'name' => $cart->room->name,
                    'image_url' => $cart->room->image_url,
                    'price' => (float) $cart->price_per_item,
                    'time' => (float) $cart->time_per_item,
                    'count' => $cart->count,
                ];
            }

            if ($cart->item) {
                $items[] = [
                    'id' => $cart->item->id,
                    'name' => $cart->item->name,
                    'image_url' => $cart->item->image_url,
                    'price' => (float) $cart->price_per_item,
                    'time' => (float) $cart->time_per_item,
                    'count' => $cart->count,
                ];
            }
        }

        return response()->json([
            'rooms' => $rooms,
            'items' => $items,
            'total_price' => round($totalPrice, 2),
            'total_time' => round($totalTime, 2),
        ], 200);
    }

    // public function removePartialFromCart(Request $request)
    // {
    //     $request->validate([
    //         'item_id' => 'nullable|integer|exists:items,id',
    //         'room_id' => 'nullable|integer|exists:rooms,id',
    //         'customization_id' => 'nullable|integer|exists:customizations,id',
    //         'room_customization_id' => 'nullable|integer|exists:room_customizations,id',
    //         'count' => 'required|integer|min:1',
    //     ]);

    //     $user = auth()->user();

    //     if (!$user || !$user->customer) {
    //         return response()->json(['message' => 'login is required'], 200);
    //     }

    //     $cartQuery = Cart::where('customer_id', $user->customer->id);

    //     if ($request->filled('item_id')) {
    //         $cartQuery->where('item_id', $request->item_id)
    //             ->whereNull('room_id')
    //             ->whereNull('customization_id')
    //             ->whereNull('room_customization_id');
    //     } elseif ($request->filled('room_id')) {
    //         $cartQuery->where('room_id', $request->room_id)
    //             ->whereNull('item_id')
    //             ->whereNull('customization_id')
    //             ->whereNull('room_customization_id');
    //     } elseif ($request->filled('customization_id')) {
    //         $cartQuery->where('customization_id', $request->customization_id)
    //             ->whereNull('item_id')
    //             ->whereNull('room_id')
    //             ->whereNull('room_customization_id');
    //     } elseif ($request->filled('room_customization_id')) {
    //         $cartQuery->where('room_customization_id', $request->room_customization_id)
    //             ->whereNull('item_id')
    //             ->whereNull('room_id')
    //             ->whereNull('customization_id');
    //     } else {
    //         return response()->json(['message' => 'You must provide one of item_id, room_id, customization_id, or room_customization_id'], 200);
    //     }

    //     $cartItem = $cartQuery->first();

    //     if (!$cartItem) {
    //         return response()->json(['message' => 'Item not found in cart'], 200);
    //     }

    //     if ($cartItem->count < $request->count) {
    //         return response()->json(['message' => 'Count you sent is bigger than count in cart'], 200);
    //     }

    //     $removalCount = $request->count;
    //     $newCartCount = $cartItem->count - $removalCount;

    //     if ($cartItem->item_id) {
    //         $item = Item::find($cartItem->item_id);

    //         if ($item) {
    //             if ($newCartCount < $cartItem->count_reserved) {
    //                 $diff = $cartItem->count_reserved - $newCartCount;
    //                 $cartItem->count_reserved = $newCartCount;
    //                 $item->count_reserved = max(0, $item->count_reserved - $diff);
    //                 $item->save();
    //             }

    //             if ($newCartCount > 0) {
    //                 $unreservedCount = max(0, $newCartCount - $cartItem->count_reserved);
    //                 $cartItem->count = $newCartCount;
    //                 $cartItem->price = $cartItem->price_per_item * $newCartCount;
    //                 $cartItem->time = $unreservedCount * $cartItem->time_per_item;
    //                 $cartItem->save();
    //             } else {
    //                 $item->count_reserved = max(0, $item->count_reserved - $cartItem->count_reserved);
    //                 $item->save();
    //                 $cartItem->delete();
    //             }
    //         }
    //     } elseif ($cartItem->room_id) {
    //         $room = Room::with('items')->find($cartItem->room_id);

    //         if ($room) {
    //             if ($newCartCount < $cartItem->count_reserved) {
    //                 $diff = $cartItem->count_reserved - $newCartCount;
    //                 $cartItem->count_reserved = $newCartCount;
    //                 $room->count_reserved = max(0, $room->count_reserved - $diff);
    //                 $room->save();

    //                 // ✅ تحرير الحجز بدقة من العناصر التابعة حسب CartItemReservation
    //                 $reservations = CartItemReservation::where('cart_id', $cartItem->id)->get();
    //                 $remainingToRemove = $diff;

    //                 foreach ($reservations as $reservation) {
    //                     if ($remainingToRemove <= 0) break;

    //                     $item = Item::find($reservation->item_id);
    //                     if (!$item) continue;

    //                     $removeNow = min($reservation->count_reserved, $remainingToRemove);

    //                     $item->count_reserved = max(0, $item->count_reserved - $removeNow);
    //                     $item->save();

    //                     $reservation->count_reserved -= $removeNow;
    //                     if ($reservation->count_reserved <= 0) {
    //                         $reservation->delete();
    //                     } else {
    //                         $reservation->save();
    //                     }

    //                     $remainingToRemove -= $removeNow;
    //                 }
    //             }

    //             if ($newCartCount > 0) {
    //                 $unreservedCount = max(0, $newCartCount - $cartItem->count_reserved);
    //                 $cartItem->count = $newCartCount;
    //                 $cartItem->price = $cartItem->price_per_item * $newCartCount;
    //                 $cartItem->time = $unreservedCount * $cartItem->time_per_item;
    //                 $cartItem->save();
    //             } else {
    //                 $room->count_reserved = max(0, $room->count_reserved - $cartItem->count_reserved);
    //                 $room->save();

    //                 // ✅ إزالة كل الحجوزات من العناصر التابعة
    //                 $reservations = CartItemReservation::where('cart_id', $cartItem->id)->get();

    //                 foreach ($reservations as $reservation) {
    //                     $item = Item::find($reservation->item_id);
    //                     if (!$item) continue;

    //                     $item->count_reserved = max(0, $item->count_reserved - $reservation->count_reserved);
    //                     $item->save();

    //                     $reservation->delete();
    //                 }

    //                 $cartItem->delete();
    //             }
    //         }
    //     }

    //     $cartItems = Cart::where('customer_id', $user->customer->id)->get();
    //     $totalPrice = $cartItems->sum('price');
    //     $totalTime = $cartItems->max('time');

    //     return response()->json([
    //         'message' => 'Item removed or updated in cart',
    //         'current_cart' => $cartItems,
    //         'total_price' => $totalPrice,
    //         'total_time' => $totalTime,
    //     ], 200);
    // }

    public function removePartialFromCart(Request $request)
    {
        $request->validate([
            'item_id' => 'nullable|integer|exists:items,id',
            'room_id' => 'nullable|integer|exists:rooms,id',
            'customization_id' => 'nullable|integer|exists:customizations,id',
            'room_customization_id' => 'nullable|integer|exists:room_customizations,id',
            'count' => 'required|integer|min:1',
        ]);

        $user = auth()->user();

        if (!$user || !$user->customer) {
            return response()->json(['message' => 'login is required'], 200);
        }

        $cartQuery = Cart::where('customer_id', $user->customer->id);

        if ($request->filled('item_id')) {
            $cartQuery->where('item_id', $request->item_id)
                ->whereNull('room_id')
                ->whereNull('customization_id')
                ->whereNull('room_customization_id');
        } elseif ($request->filled('room_id')) {
            $cartQuery->where('room_id', $request->room_id)
                ->whereNull('item_id')
                ->whereNull('customization_id')
                ->whereNull('room_customization_id');
        } elseif ($request->filled('customization_id')) {
            $cartQuery->where('customization_id', $request->customization_id)
                ->whereNull('item_id')
                ->whereNull('room_id')
                ->whereNull('room_customization_id');
        } elseif ($request->filled('room_customization_id')) {
            $cartQuery->where('room_customization_id', $request->room_customization_id)
                ->whereNull('item_id')
                ->whereNull('room_id')
                ->whereNull('customization_id');
        } else {
            return response()->json(['message' => 'You must provide one of item_id, room_id, customization_id, or room_customization_id'], 200);
        }

        $cart = $cartQuery->first();

        if (!$cart) {
            return response()->json(['message' => 'Item not found in cart'], 200);
        }

        if ($cart->count < $request->count) {
            return response()->json(['message' => 'Count you sent is bigger than count in cart'], 200);
        }

        $cart->count -= $request->count;
        if ($cart->count <= 0) {
            $cart->delete();
        } else {
            $cart->save();

            if ($cart->item_id) {
                $item = Item::find($cart->item_id);
                if ($item) {
                    $item->count_reserved = max(0, $item->count_reserved - $cart->count_reserved);
                    $item->save();

                    $availableCount = max(0, $item->count - $item->count_reserved);
                    $newReservedCount = min($cart->count, $availableCount);
                    $missingCount = $cart->count - $newReservedCount;
                    $timeForMissing = $missingCount * $item->time;

                    $cart->count_reserved = $newReservedCount;
                    $cart->time = $timeForMissing;
                    $cart->reserved_at = Carbon::now();
                    $cart->save();

                    $item->count_reserved += $newReservedCount;
                    $item->count_reserved = min($item->count_reserved, $item->count);
                    $item->save();
                }
            } elseif ($cart->room_id) {
                $room = Room::with('items')->find($cart->room_id);
                if ($room) {
                    $room->count_reserved = max(0, $room->count_reserved - $cart->count_reserved);
                    $room->save();

                    foreach ($room->items as $roomItem) {
                        $roomItem->count_reserved = max(0, $roomItem->count_reserved - $cart->count_reserved);
                        $roomItem->save();
                    }

                    $availableRooms = max(0, $room->count - $room->count_reserved);
                    $maxReservable = min($cart->count, $availableRooms);
                    $missingTime = 0;

                    foreach ($room->items as $roomItem) {
                        $availableItem = max(0, $roomItem->count - $roomItem->count_reserved);
                        if ($availableItem < $maxReservable) {
                            $reduction = $maxReservable - $availableItem;
                            $maxReservable = $availableItem;
                            $missingItemCount = $cart->count - $maxReservable;
                            $missingTime += $missingItemCount * $roomItem->time;
                        }
                    }

                    $missingRoomCount = $cart->count - $maxReservable;
                    $missingTime += $missingRoomCount * $room->time;

                    $cart->count_reserved = $maxReservable;
                    $cart->time = $missingTime;
                    $cart->reserved_at = Carbon::now();
                    $cart->save();

                    $room->count_reserved += $maxReservable;
                    $room->count_reserved = min($room->count_reserved, $room->count);
                    $room->save();

                    foreach ($room->items as $roomItem) {
                        $roomItem->count_reserved += $maxReservable;
                        $roomItem->count_reserved = min($roomItem->count_reserved, $roomItem->count);
                        $roomItem->save();
                    }

                    // 🆕 تحديث cart_item_reservations
                    $deletedCount = $request->count;
                    $cartItemReservations = CartItemReservation::where('cart_id', $cart->id)->get();

                    foreach ($cartItemReservations as $reservation) {
                        if ($deletedCount <= 0) break;

                        $toReduce = min($reservation->count_reserved, $deletedCount);
                        $reservation->count_reserved -= $toReduce;
                        $reservation->save();

                        $deletedCount -= $toReduce;
                    }
                }
            }
        }

        $cartItems = Cart::where('customer_id', $user->customer->id)->get();
        $totalPrice = $cartItems->sum('price');
        $totalTime = $cartItems->max('time');

        return response()->json([
            'message' => 'Item removed or updated in cart',
            'current_cart' => $cartItems,
            'total_price' => $totalPrice,
            'total_time' => $totalTime,
        ], 200);
    }


    // public function deleteCart()
    // {
    //     $user = auth()->user();
    //     $customerId = $user->customer->id;

    //     $cartItems = Cart::where('customer_id', $customerId)->get();

    //     foreach ($cartItems as $cartItem) {
    //         if ($cartItem->item_id) {
    //             $item = Item::find($cartItem->item_id);
    //             if ($item) {
    //                 $item->count_reserved = max(0, $item->count_reserved - $cartItem->count);
    //                 $item->save();
    //             }
    //         } elseif ($cartItem->room_id) {
    //             $room = Room::with('items')->find($cartItem->room_id);
    //             if ($room) {
    //                 // 🔁 تحرير الحجز من الغرفة نفسها
    //                 $room->count_reserved = max(0, $room->count_reserved - $cartItem->count);
    //                 $room->save();

    //                 // 🔁 تحرير الحجز من العناصر التابعة للغرفة
    //                 foreach ($room->items as $roomItem) {
    //                     $roomItem->count_reserved = max(0, $roomItem->count_reserved - $cartItem->count);
    //                     $roomItem->save();
    //                 }
    //             }
    //         }
    //     }

    //     Cart::where('customer_id', $customerId)->delete();

    //     return response()->json(['message' => 'Cart and reservations cleared']);
    // }
    public function deleteCart()
    {
        $user = auth()->user();
        $customerId = $user->customer->id;

        $cartItems = Cart::where('customer_id', $customerId)->get();

        foreach ($cartItems as $cartItem) {
            if ($cartItem->item_id) {
                $item = Item::find($cartItem->item_id);
                if ($item) {
                    $item->count_reserved = max(0, $item->count_reserved - $cartItem->count_reserved);
                    $item->save();
                }
            } elseif ($cartItem->room_id) {
                $room = Room::with('items')->find($cartItem->room_id);
                if ($room) {
                    // 1️⃣ تحرير الحجز من الغرفة نفسها
                    $room->count_reserved = max(0, $room->count_reserved - $cartItem->count_reserved);
                    $room->save();

                    // 2️⃣ تحرير الحجز من العناصر التابعة بناءً على CartItemReservation
                    $reservations = CartItemReservation::where('cart_id', $cartItem->id)->get();

                    foreach ($reservations as $reservation) {
                        $item = Item::find($reservation->item_id);
                        if ($item) {
                            $item->count_reserved = max(0, $item->count_reserved - $reservation->count_reserved);
                            $item->save();
                        }

                        // حذف السطر من جدول الربط بعد التعديل
                        $reservation->delete();
                    }
                }
            }
        }

        // 3️⃣ حذف السلة كاملة بعد تحديث الجداول المرتبطة
        Cart::where('customer_id', $customerId)->delete();

        return response()->json(['message' => 'Cart and reservations cleared']);
    }


    // public function confirmCart(Request $request)
    // {
    //     $user = auth()->user();
    //     $customerId = $user->customer->id;
    //     $cartItems = Cart::where('customer_id', $customerId)->get();

    //     if ($cartItems->isEmpty()) {
    //         return response()->json(['message' => 'Your cart is empty'], 200);
    //     }

    //     $wantDelivery = $request->input('want_delivery');
    //     if (!in_array($wantDelivery, ['yes', 'no'])) {
    //         return response()->json(['message' => 'The field want_delivery is required and must be yes or no']);
    //     }

    //     if ($wantDelivery === 'yes') {
    //         if (!$request->has(['latitude', 'longitude'])) {
    //             return response()->json(['message' => 'Latitude and longitude are required when delivery is wanted.']);
    //         }
    //         if (!$request->has('address') || empty($request->input('address'))) {
    //             return response()->json(['message' => 'Address is required when delivery is wanted.']);
    //         }
    //     }

    //     $totalPrice = 0;
    //     $totalTime = 0;

    //     foreach ($cartItems as $cartItem) {
    //         $totalPrice += $cartItem->price;
    //         $totalTime += $cartItem->time;
    //     }

    //     $rabbon = $totalPrice * 0.5;
    //     $priceAfterRabbon = $totalPrice - $rabbon;

    //     $wallet = $user->wallets()->where('is_active', 1)->where('wallet_type', 'investment')->first();

    //     if (!$wallet) {
    //         return response()->json(['message' => 'No active wallet found'], 400);
    //     }

    //     if ($wallet->balance < $rabbon) {
    //         return response()->json(['message' => 'Insufficient balance to pay the deposit (rabbon)'], 200);
    //     }

    //     $wallet->balance -= $rabbon;
    //     $wallet->save();

    //     $manager = GallaryManager::with('user.wallets')->first();
    //     if (!$manager || !$manager->user || !$manager->user->wallets->first()) {
    //         return response()->json(['message' => 'Manager wallet not found'], 500);
    //     }

    //     $managerWallet = $manager->user->wallets->first();
    //     $managerWallet->balance += $rabbon;
    //     $managerWallet->save();

    //     $deliveryPrice = 0;
    //     $nearestBranch = null;

    //     if ($wantDelivery === 'yes') {
    //         $deliveryRequest = new \Illuminate\Http\Request([
    //             'address'   => $request->input('address'),
    //             'latitude'  => $request->input('latitude'),
    //             'longitude' => $request->input('longitude'),
    //         ]);

    //         $deliveryResponse = $this->getDeliveryPrice($deliveryRequest);
    //         $responseData = $deliveryResponse->getData(true);

    //         if ($deliveryResponse->getStatusCode() === 200) {
    //             $deliveryPrice = $responseData['delivery_price'];
    //         } else {
    //             return response()->json(['message' => $responseData['message']]);
    //         }
    //     } else {
    //         $branchRequest = new \Illuminate\Http\Request([
    //             'latitude'  => $request->input('latitude'),
    //             'longitude' => $request->input('longitude'),
    //         ]);

    //         $branchResponse = $this->getNearestBranch($branchRequest);
    //         $responseData = $branchResponse->getData(true);

    //         if ($branchResponse->getStatusCode() === 200) {
    //             $nearestBranch = $responseData['branch'];
    //         } else {
    //             return response()->json(['message' => $responseData['message']]);
    //         }
    //     }

    //     $priceAfterRabbonWithDelivery = $priceAfterRabbon + $deliveryPrice;
    //     $remainingAmountWithDelivery = $wantDelivery === 'yes' ? $priceAfterRabbonWithDelivery : null;

    //     $purchaseOrder = PurchaseOrder::create([
    //         'customer_id'                        => $customerId,
    //         'status'                             => 'in_progress',
    //         'delivery_status'                    => 'pending',
    //         'want_delivery'                      => $wantDelivery,
    //         'is_paid'                            => 'pending',
    //         'total_price'                        => $totalPrice,
    //         'recive_date'                        => $request->input('recive_date', now()),
    //         'latitude'                           => $request->input('latitude'),
    //         'longitude'                          => $request->input('longitude'),
    //         'address'                            => $request->input('address'),
    //         'delivery_price'                     => $deliveryPrice,
    //         'rabbon'                             => $rabbon,
    //         'price_after_rabbon'                 => $priceAfterRabbon,
    //         'price_after_rabbon_with_delivery'   => $wantDelivery === 'yes' ? $priceAfterRabbonWithDelivery : null,
    //         'remaining_amount_with_delivery'     => $remainingAmountWithDelivery,
    //         'branch_id'                          => $wantDelivery === 'no' && $nearestBranch ? $nearestBranch['id'] : null,
    //     ]);

    //     foreach ($cartItems as $cartItem) {
    //         $countRequested = $cartItem->count;

    //         if ($cartItem->item_id) {
    //             $item = Item::find($cartItem->item_id);
    //             if ($item) {
    //                 // خصم العدد المحجوز من العنصر
    //                 $item->count -= $cartItem->count_reserved;
    //                 $item->count_reserved -= $cartItem->count_reserved;
    //                 $item->count = max(0, $item->count);
    //                 $item->count_reserved = max(0, $item->count_reserved);
    //                 $item->save();

    //                 ItemOrder::create([
    //                     'item_id'           => $item->id,
    //                     'purchase_order_id' => $purchaseOrder->id,
    //                     'count'             => $cartItem->count,
    //                     'price'             => $cartItem->price,
    //                     'time'              => $cartItem->time,
    //                     'count_reserved'    => $cartItem->count_reserved,
    //                 ]);
    //             }
    //         }

    //         if ($cartItem->room_id) {
    //             $room = Room::find($cartItem->room_id);
    //             if ($room) {
    //                 // خصم العدد المحجوز من الغرفة نفسها
    //                 $room->count -= $cartItem->count_reserved;
    //                 $room->count_reserved -= $cartItem->count_reserved;
    //                 $room->count = max(0, $room->count);
    //                 $room->count_reserved = max(0, $room->count_reserved);
    //                 $room->save();
    //             }

    //             $roomOrder = $purchaseOrder->roomOrders()->create([
    //                 'room_id'        => $cartItem->room_id,
    //                 'count'          => $cartItem->count,
    //                 'deposite_price' => $cartItem->price,
    //                 'deposite_time'  => $cartItem->time,
    //                 'count_reserved' => $cartItem->count_reserved,
    //             ]);
    //         }

    //         $cartItem->delete();
    //     }

    //     $branch = null;
    //     if ($purchaseOrder->branch_id) {
    //         $branch = Branch::find($purchaseOrder->branch_id);
    //     }

    //     return response()->json([
    //         'message' => 'Your order has been confirmed successfully!',
    //         'order'   => $purchaseOrder->load('roomOrders', 'item', 'customer'),
    //         'price_details' => [
    //             'total_price'                       => $totalPrice,
    //             'rabbon'                            => $rabbon,
    //             'price_after_rabbon'                => $priceAfterRabbon,
    //             'delivery_price'                    => $deliveryPrice,
    //             'price_after_rabbon_with_delivery' => $priceAfterRabbonWithDelivery,
    //             'remaining_amount'                  => $priceAfterRabbon,
    //             'remaining_amount_with_delivery'   => $remainingAmountWithDelivery,
    //         ],
    //         'nearest_branch' => $nearestBranch,
    //     ]);
    // }

    public function confirmCart(Request $request)
    {
        $user = auth()->user();
        $customerId = $user->customer->id;
        $cartItems = Cart::where('customer_id', $customerId)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty'], 200);
        }

        $wantDelivery = $request->input('want_delivery');
        if (!in_array($wantDelivery, ['yes', 'no'])) {
            return response()->json(['message' => 'The field want_delivery is required and must be yes or no']);
        }

        if ($wantDelivery === 'yes') {
            if (!$request->has(['latitude', 'longitude'])) {
                return response()->json(['message' => 'Latitude and longitude are required when delivery is wanted.']);
            }
            if (!$request->has('address') || empty($request->input('address'))) {
                return response()->json(['message' => 'Address is required when delivery is wanted.']);
            }
        }

        $totalPrice = 0;
        $totalTime = 0;

        foreach ($cartItems as $cartItem) {
            $totalPrice += $cartItem->price;
            if ($cartItem->time > $totalTime) {
                $totalTime = $cartItem->time;
            }
        }


        $rabbon = $totalPrice * 0.5;
        $priceAfterRabbon = $totalPrice - $rabbon;

        $wallet = $user->wallets()->where('is_active', 1)->where('wallet_type', 'investment')->first();

        if (!$wallet) {
            return response()->json(['message' => 'No active wallet found'], 400);
        }

        if ($wallet->balance < $rabbon) {
            return response()->json(['message' => 'Insufficient balance to pay the deposit (rabbon)'], 200);
        }

        $wallet->balance -= $rabbon;
        $wallet->save();

        $manager = GallaryManager::with('user.wallets')->first();
        if (!$manager || !$manager->user || !$manager->user->wallets->first()) {
            return response()->json(['message' => 'Manager wallet not found'], 500);
        }

        $managerWallet = $manager->user->wallets->first();
        $managerWallet->balance += $rabbon;
        $managerWallet->save();

        $deliveryPrice = 0;
        $nearestBranch = null;

        if ($wantDelivery === 'yes') {
            $deliveryRequest = new \Illuminate\Http\Request([
                'address'   => $request->input('address'),
                'latitude'  => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
            ]);

            $deliveryResponse = $this->getDeliveryPrice($deliveryRequest);
            $responseData = $deliveryResponse->getData(true);

            if ($deliveryResponse->getStatusCode() === 200) {
                $deliveryPrice = $responseData['delivery_price'];
            } else {
                return response()->json(['message' => $responseData['message']]);
            }
        } else {
            $branchRequest = new \Illuminate\Http\Request([
                'latitude'  => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
            ]);

            $branchResponse = $this->getNearestBranch($branchRequest);
            $responseData = $branchResponse->getData(true);

            if ($branchResponse->getStatusCode() === 200) {
                $nearestBranch = $responseData['branch'];
            } else {
                return response()->json(['message' => $responseData['message']]);
            }
        }

        $priceAfterRabbonWithDelivery = $priceAfterRabbon + $deliveryPrice;
        $remainingAmountWithDelivery = $wantDelivery === 'yes' ? $priceAfterRabbonWithDelivery : null;

        $purchaseOrder = PurchaseOrder::create([
            'customer_id'                        => $customerId,
            'status'                             => 'in_progress',
            'delivery_status'                    => 'pending',
            'want_delivery'                      => $wantDelivery,
            'is_paid'                            => 'pending',
            'total_price'                        => $totalPrice,
            'recive_date'                        => $request->input('recive_date', now()),
            'latitude'                           => $request->input('latitude'),
            'longitude'                          => $request->input('longitude'),
            'address'                            => $request->input('address'),
            'delivery_price'                     => $deliveryPrice,
            'rabbon'                             => $rabbon,
            'price_after_rabbon'                 => $priceAfterRabbon,
            'price_after_rabbon_with_delivery'   => $wantDelivery === 'yes' ? $priceAfterRabbonWithDelivery : null,
            'remaining_amount_with_delivery'     => $remainingAmountWithDelivery,
            'branch_id'                          => $wantDelivery === 'no' && $nearestBranch ? $nearestBranch['id'] : null,
        ]);


        foreach ($cartItems as $cartItem) {
            $countRequested = $cartItem->count;

            if ($cartItem->item_id) {
                $item = Item::find($cartItem->item_id);
                if ($item) {
                    $item->count -= $cartItem->count_reserved;
                    $item->count_reserved -= $cartItem->count_reserved;
                    $item->count = max(0, $item->count);
                    $item->count_reserved = max(0, $item->count_reserved);
                    $item->save();

                    $missing = $cartItem->count - $cartItem->count_reserved;
                    if ($missing > 0) {
                        \App\Models\WorkshopManagerRequest::create([
                            'purchase_order_id' => $purchaseOrder->id,
                            'item_id'           => $item->id,
                            'required_count'    => $missing,
                            'branch_id'         => $purchaseOrder->branch_id,
                        ]);
                    }

                    ItemOrder::create([
                        'item_id'           => $item->id,
                        'purchase_order_id' => $purchaseOrder->id,
                        'count'             => $cartItem->count,
                        'price'             => $cartItem->price,
                        'time'              => $cartItem->time,
                        'count_reserved'    => $cartItem->count_reserved,
                    ]);
                }
            }

            if ($cartItem->room_id) {
                $room = Room::find($cartItem->room_id);
                if ($room) {
                    $room->count -= $cartItem->count_reserved;
                    $room->count_reserved -= $cartItem->count_reserved;
                    $room->count = max(0, $room->count);
                    $room->count_reserved = max(0, $room->count_reserved);
                    $room->save();

                    $missing = $cartItem->count - $cartItem->count_reserved;
                    if ($missing > 0) {
                        \App\Models\WorkshopManagerRequest::create([
                            'purchase_order_id' => $purchaseOrder->id,
                            'room_id'           => $room->id,
                            'required_count'    => $missing,
                            'branch_id'         => $purchaseOrder->branch_id,
                        ]);
                    }
                    $reservations = \App\Models\CartItemReservation::where('cart_id', $cartItem->id)->get();
                    foreach ($reservations as $reservation) {
                        $item = Item::find($reservation->item_id);
                        if ($item) {
                            $item->count -= $reservation->count_reserved;
                            $item->count_reserved -= $reservation->count_reserved;
                            $item->count = max(0, $item->count);
                            $item->count_reserved = max(0, $item->count_reserved);
                            $item->save();

                            $missing = $cartItem->count - $reservation->count_reserved;
                            if ($missing > 0) {
                                \App\Models\WorkshopManagerRequest::create([
                                    'purchase_order_id' => $purchaseOrder->id,
                                    'item_id'           => $item->id,
                                    'room_id'           => $room->id,
                                    'required_count'    => $missing,
                                    'branch_id'         => $purchaseOrder->branch_id,
                                ]);
                            }
                        }

                        \App\Models\RoomItemOrder::create([
                            'purchase_order_id' => $purchaseOrder->id,
                            'room_id'           => $room->id,
                            'item_id'           => $reservation->item_id,
                            'count_reserved'    => $reservation->count_reserved,
                        ]);

                        $reservation->delete();
                    }

                    $purchaseOrder->roomOrders()->create([
                        'room_id'        => $cartItem->room_id,
                        'count'          => $cartItem->count,
                        'deposite_price' => $cartItem->price,
                        'deposite_time'  => $cartItem->time,
                        'count_reserved' => $cartItem->count_reserved,
                    ]);
                }
            }

            $cartItem->delete();
        }

        $branch = Branch::find($purchaseOrder->branch_id) ?? null;
        if (!$branch) {
            return response()->json(['message' => 'Branch not found'], 404);
        }

        if ($totalTime == 0) {
            // TODO: إرسال إشعار للزبون بأن "طلبك جاهز للاستلام"
        }

        return response()->json([
            'message' => 'Your order has been confirmed successfully!',
            'order'   => $purchaseOrder->load('roomOrders', 'item', 'customer'),
            'price_details' => [
                'total_price'                       => $totalPrice,
                'rabbon'                            => $rabbon,
                'price_after_rabbon'                => $priceAfterRabbon,
                'delivery_price'                    => $deliveryPrice,
                'price_after_rabbon_with_delivery'  => $priceAfterRabbonWithDelivery,
                'remaining_amount'                  => $priceAfterRabbon,
                'remaining_amount_with_delivery'    => $remainingAmountWithDelivery,
            ],
            'nearest_branch' => $nearestBranch,
        ]);
    }

    protected function findAvailableDeliveryTime()
    {
        $customerId = auth()->user()->customer->id;

        $customerTimes = AvailableTime::where('customer_id', $customerId)
            ->pluck('available_at');

        $companyAvailability = DeliveryCompanyAvailability::get()
            ->keyBy('day_of_week');

        $bookedTimes = PurchaseOrder::whereNotNull('delivery_time')
            ->pluck('delivery_time')
            ->map(fn($time) => Carbon::parse($time)->format('Y-m-d H:i'))
            ->toArray();

        foreach ($customerTimes as $time) {
            $carbonTime = Carbon::parse($time);
            $formattedTime = $carbonTime->format('Y-m-d H:i');
            $dayName = strtolower($carbonTime->format('l'));

            if (!$companyAvailability->has($dayName)) {
                continue;
            }

            $startTime = $companyAvailability[$dayName]->start_time;
            $endTime = $companyAvailability[$dayName]->end_time;

            $timeOnly = $carbonTime->format('H:i:s');

            if ($timeOnly >= $startTime && $timeOnly <= $endTime) {
                if (!in_array($formattedTime, $bookedTimes)) {
                    return $formattedTime;
                }
            }
        }

        return null;
    }

    public function getNearestBranch(Request $request)
    {
        $userLat = $request->input('latitude');
        $userLng = $request->input('longitude');

        if (!$userLat || !$userLng) {
            return response()->json(['message' => 'Latitude and longitude are required.'], 422);
        }

        $nearestBranch = Branch::selectRaw("*, 
        (6371 * acos(cos(radians(?)) * cos(radians(latitude)) 
        * cos(radians(longitude) - radians(?)) 
        + sin(radians(?)) * sin(radians(latitude)))) AS distance", [
            $userLat,
            $userLng,
            $userLat
        ])
            ->orderBy('distance')
            ->first();

        if (!$nearestBranch) {
            return response()->json(['message' => 'No branches found.'], 404);
        }

        return response()->json([
            'message' => 'Nearest branch retrieved successfully.',
            'branch' => [
                'id'          => $nearestBranch->id,
                'address'     => $nearestBranch->address,
                'latitude'    => $nearestBranch->latitude,
                'longitude'   => $nearestBranch->longitude,
                'distance_km' => round($nearestBranch->distance, 2),
            ]
        ]);
    }

    public function getDeliveryPrice(Request $request)
    {
        $request->validate([
            'address'   => 'required|string',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $placeCost = PlaceCost::where('place', $request->input('address'))->first();

        if (!$placeCost) {
            return response()->json([
                'message' => 'Delivery price not found for the given address.',
                'delivery_price' => null
            ], 404);
        }

        $deliveryPrice = $placeCost->price;

        $customerId = auth()->user()->customer->id;
        $cartItems = \App\Models\Cart::where('customer_id', $customerId)->get();

        $totalCartPrice = $cartItems->sum('price');
        $totalWithDelivery = $totalCartPrice + $deliveryPrice;

        // ✅ حساب الرعبون (50%) والسعر بعده مع التوصيل
        $depositAmount = $totalCartPrice * 0.5;
        $priceAfterDepositAndDelivery = $depositAmount + $deliveryPrice;

        return response()->json([
            'message' => 'Delivery price and total price with delivery retrieved successfully.',
            'delivery_price' => round($deliveryPrice, 2),
            'total_price_with_delivery' => round($totalWithDelivery, 2),
            'price_after_deposit_and_delivery' => round($priceAfterDepositAndDelivery, 2)
        ]);
    }
}
