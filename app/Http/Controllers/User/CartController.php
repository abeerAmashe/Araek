<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AvailableTime;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\CartItemReservation;
use App\Models\Customization;
use App\Models\DeliveryCompanyAvailability;
use App\Models\GallaryManager;
use App\Models\Item;
use App\Models\ItemOrder;
use App\Models\PlaceCost;
use App\Models\PurchaseOrder;
use App\Models\Room;
use App\Models\RoomCustomization;
use App\Models\WorkshopManagerRequest;
use BilalMardini\FirebaseNotification\Facades\FirebaseNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class  CartController extends Controller
{
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
        } elseif ($customizationId) {
            $customization = Customization::find($customizationId);
            if (!$customization) {
                return response()->json(['message' => 'Item customization not found'], 200);
            }

            $pricePerItem = (float) $customization->final_price;
            $timePerItem = (float) $customization->final_time;
            $partialTime = $count * $timePerItem;


            $cart = Cart::firstOrCreate(
                [
                    'customer_id' => $customerId,
                    'customization_id' => $customizationId,
                ],
                [
                    'count' => 0,
                    'count_reserved' => 0,
                    'time_per_item' => $timePerItem,
                    'price_per_item' => $pricePerItem,
                    'time' => 0,
                    'price' => 0,
                    'available_count_at_addition' => 0,
                    'reserved_at' => now(),
                ]
            );

            $cart->count += $count;
            $cart->time_per_item = $timePerItem;
            $cart->price_per_item = $pricePerItem;
            $cart->price = $cart->count * $pricePerItem;
            $cart->time = $cart->count * $timePerItem;
            $cart->reserved_at = now();
            $cart->save();
        } elseif ($roomCustomizationId) {
            $roomCustomization = RoomCustomization::find($roomCustomizationId);
            if (!$roomCustomization) {
                return response()->json(['message' => 'Room customization not found'], 200);
            }

            $pricePerItem = (float) $roomCustomization->final_price;
            $timePerItem = (float) $roomCustomization->final_time;
            $partialTime = $count * $timePerItem;

            $cart = Cart::firstOrCreate(
                [
                    'customer_id' => $customerId,
                    'room_customization_id' => $roomCustomizationId,
                ],
                [
                    'count' => 0,
                    'count_reserved' => 0,
                    'time_per_item' => $timePerItem,
                    'price_per_item' => $pricePerItem,
                    'time' => 0,
                    'price' => 0,
                    'available_count_at_addition' => 0,
                    'reserved_at' => now(),
                ]
            );

            $cart->count += $count;
            $cart->time_per_item = $timePerItem;
            $cart->price_per_item = $pricePerItem;
            $cart->price = $cart->count * $pricePerItem;
            $cart->time = $cart->count * $timePerItem;
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

        $cartItems = \App\Models\Cart::with([
            'item',
            'room',
            'customization',
            'customization.item',
            'customization.wood',
            'customization.fabric',
        ])
            ->where('customer_id', $customerId)
            ->get();

        $rooms = [];
        $items = [];
        $customizedItems = [];
        $customizedRooms = [];

        $totalPrice = 0;
        $totalTime = 0;

        foreach ($cartItems as $cart) {
            $lineTotalPrice = (float) $cart->price;
            $lineTotalTime = (float) $cart->time;

            $totalPrice += $lineTotalPrice;
            $totalTime += $lineTotalTime;

            if ($cart->room && !$cart->room_customization_id) {
                $rooms[] = [
                    'id' => $cart->room->id,
                    'name' => $cart->room->name,
                    'image_url' => $cart->room->image_url,
                    'price' => (float) $cart->price_per_item,
                    'time' => (float) $cart->time_per_item,
                    'count' => $cart->count,
                ];
            }

            if ($cart->item && !$cart->customization_id) {
                $items[] = [
                    'id' => $cart->item->id,
                    'name' => $cart->item->name,
                    'image_url' => $cart->item->image_url,
                    'price' => (float) $cart->price_per_item,
                    'time' => (float) $cart->time_per_item,
                    'count' => $cart->count,
                ];
            }

            if ($cart->customization) {
                $customizedItems[] = [
                    'id' => $cart->customization->id,
                    'name' => $cart->customization->item->name . ' (Customized)',
                    'image_url' => $cart->customization->item->image_url,
                    'price' => (float) $cart->price_per_item,
                    'time' => (float) $cart->time_per_item,
                    'count' => $cart->count,
                    'customization' => [
                        'wood_type' => optional($cart->customization->wood)->name,
                        'fabric_type' => optional($cart->customization->fabric)->name,
                        'wood_color' => $cart->customization->wood_color,
                        'fabric_color' => $cart->customization->fabric_color,
                        'dimensions' => [
                            'length' => $cart->customization->new_length,
                            'width' => $cart->customization->new_width,
                            'height' => $cart->customization->new_height,
                        ],
                    ],
                ];
            }

            if ($cart->room_customization_id) {
                $roomCustomization = RoomCustomization::with([
                    'room',
                    'woodType',
                    'fabricType',
                    'woodColor',
                    'fabricColor',
                ])->find($cart->room_customization_id);

                if ($roomCustomization) {
                    $customizedRooms[] = [
                        'id' => $roomCustomization->id,
                        'name' => optional($roomCustomization->room)->name,
                        'image_url' => optional($roomCustomization->room)->image_url,
                        'price' => (float) $roomCustomization->final_price,
                        'time' => (float) $roomCustomization->final_time,
                        'count' => $cart->count,
                        'customization' => [
                            'wood_type' => optional($roomCustomization->woodType)->name,
                            'fabric_type' => optional($roomCustomization->fabricType)->name,
                            'wood_color' => optional($roomCustomization->woodColor)->name,
                            'fabric_color' => optional($roomCustomization->fabricColor)->name,
                        ],
                    ];
                }
            }
        }

        return response()->json([
            'rooms' => $rooms,
            'items' => $items,
            'customized_items' => $customizedItems,
            'customized_rooms' => $customizedRooms,
            'total_price' => round($totalPrice, 2),
            'total_time' => round($totalTime, 2),
        ], 200);
    }

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

        $countToRemove = $request->count;
        $newCount = $cart->count - $countToRemove;

        $priceToRemove = 0;
        $timeToRemove = 0;

        if ($cart->customization_id) {
            $customization = Customization::find($cart->customization_id);
            $priceToRemove = $countToRemove * $customization->final_price;
            $timeToRemove = $countToRemove * $customization->final_time;
        } elseif ($cart->room_customization_id) {
            $roomCustomization = RoomCustomization::find($cart->room_customization_id);
            $priceToRemove = $countToRemove * $roomCustomization->final_price;
            $timeToRemove = $countToRemove * $roomCustomization->final_time;
        } else {
            $priceToRemove = $countToRemove * ($cart->price_per_item ?? 0);
            $timeToRemove = $countToRemove * ($cart->time_per_item ?? 0);
        }

        $cart->count = $newCount;
        $cart->price = max(0, $cart->price - $priceToRemove);
        $cart->time = max(0, $cart->time - $timeToRemove);

        if ($cart->count <= 0) {
            $cart->delete();
        } else {
            $cart->save();
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
                    $room->count_reserved = max(0, $room->count_reserved - $cartItem->count_reserved);
                    $room->save();

                    $reservations = CartItemReservation::where('cart_id', $cartItem->id)->get();

                    foreach ($reservations as $reservation) {
                        $item = Item::find($reservation->item_id);
                        if ($item) {
                            $item->count_reserved = max(0, $item->count_reserved - $reservation->count_reserved);
                            $item->save();
                        }

                        $reservation->delete();
                    }
                }
            }
        }

        Cart::where('customer_id', $customerId)->delete();

        return response()->json(['message' => 'Cart and reservations cleared']);
    }

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

        // if ($wantDelivery === 'yes') {
        //     $deliveryRequest = new \Illuminate\Http\Request([
        //         'address'   => $request->input('address'),
        //         'latitude'  => $request->input('latitude'),
        //         'longitude' => $request->input('longitude'),
        //     ]);

        //     $deliveryResponse = $this->getDeliveryPrice($deliveryRequest);
        //     $responseData = $deliveryResponse->getData(true);

        //     if ($deliveryResponse->getStatusCode() === 200) {
        //         $deliveryPrice = $responseData['delivery_price'];
        //     } else {
        //         return response()->json(['message' => $responseData['message']]);
        //     }
        // } else {
        //     $branchRequest = new \Illuminate\Http\Request([
        //         'latitude'  => $request->input('latitude'),
        //         'longitude' => $request->input('longitude'),
        //     ]);

        //     $branchResponse = $this->getNearestBranch($branchRequest);
        //     $responseData = $branchResponse->getData(true);

        //     if ($branchResponse->getStatusCode() === 200) {
        //         $nearestBranch = $responseData['branch'];
        //     } else {
        //         return response()->json(['message' => $responseData['message']]);
        //     }
        // }
        $deliveryPrice = 0;
        $nearestBranch = null;

        if ($wantDelivery === 'yes') {
            // حساب تكلفة التوصيل
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
        }

        // جلب أقرب فرع (سواء كان delivery yes أو no)
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
            'branch_id' => $nearestBranch ? $nearestBranch['id'] : null,
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

            if ($cartItem->customization_id) {
                $customization = \App\Models\Customization::find($cartItem->customization_id);
                if ($customization) {
                    \App\Models\WorkshopManagerRequest::create([
                        'purchase_order_id'  => $purchaseOrder->id,
                        'customization_id' => $customization->id,
                        'required_count'     => $cartItem->count,
                        'branch_id'          => $purchaseOrder->branch_id,
                    ]);

                    \App\Models\CustomizationOrder::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'customization_id' => $customization->id,
                        'count'              => $cartItem->count,
                        'price'              => $cartItem->price,
                        'time'               => $cartItem->time,
                    ]);
                }
            }

            if ($cartItem->room_customization_id) {
                $roomCustomization = \App\Models\RoomCustomization::find($cartItem->room_customization_id);
                if ($roomCustomization) {
                    \App\Models\WorkshopManagerRequest::create([
                        'purchase_order_id'   => $purchaseOrder->id,
                        'room_customization_id'  => $roomCustomization->id,
                        'required_count'      => $cartItem->count,
                        'branch_id'           => $purchaseOrder->branch_id,
                    ]);

                    \App\Models\RoomCustomizationOrder::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'room_customization_id' => $roomCustomization->id,
                        'count'              => $cartItem->count,
                        'price'              => $cartItem->price,
                        'time'               => $cartItem->time,
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
            if ($user->userFcmTokens->isNotEmpty()) {
                Log::info('Firebase credentials path:', [config('firebase.credentials_file_path')]);

                try {
                    FirebaseNotification::setTitle('Your order is ready for pickup')
                        ->setBody('Your order is now ready. Please choose a suitable time to pick it up from the branch.')
                        ->setUsers(collect([$user]))
                        ->setData([
                            'order_id' => $purchaseOrder->id,
                            'type' => 'order_ready'
                        ])
                        ->push();
                } catch (\Exception $e) {
                    Log::error('Failed to send ready order notification', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
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

    // protected function findAvailableDeliveryTime()
    // {
    //     $user = auth()->user();
    //     $purchaseOrder = $user->customer->purchaseOrders;

    //     $customerId = auth()->user()->customer->id;

    //     $customerTimes = AvailableTime::where('customer_id', $customerId)
    //         ->pluck('available_at');

    //     $companyAvailability = DeliveryCompanyAvailability::get()
    //         ->keyBy('day_of_week');

    //     $bookedTimes = PurchaseOrder::whereNotNull('delivery_time')
    //         ->pluck('delivery_time')
    //         ->map(fn($time) => Carbon::parse($time)->format('Y-m-d H:i'))
    //         ->toArray();

    //     foreach ($customerTimes as $time) {
    //         $carbonTime = Carbon::parse($time);
    //         $formattedTime = $carbonTime->format('Y-m-d H:i');
    //         $dayName = strtolower($carbonTime->format('l'));

    //         if (!$companyAvailability->has($dayName)) {
    //             continue;
    //         }

    //         $startTime = $companyAvailability[$dayName]->start_time;
    //         $endTime = $companyAvailability[$dayName]->end_time;

    //         $timeOnly = $carbonTime->format('H:i:s');

    //         if ($timeOnly >= $startTime && $timeOnly <= $endTime) {
    //             if (!in_array($formattedTime, $bookedTimes)) {

    //                 if ($user->userFcmTokens->exists()) {
    //                     try {
    //                         FirebaseNotification::setTitle('Delivery Appointment Scheduled')
    //                             ->setBody("A delivery appointment has been scheduled for your order: {$formattedTime}")
    //                             ->setUsers(collect([$user]))
    //                             ->setData([
    //                                 'order_id' => $purchaseOrder->id,
    //                                 'delivery_time' => $formattedTime,
    //                                 'type' => 'delivery_scheduled'
    //                             ])
    //                             ->push();
    //                     } catch (\Exception $e) {
    //                         Log::error('Failed to send delivery appointment notification', [
    //                             'user_id' => $user->id,
    //                             'error' => $e->getMessage(),
    //                         ]);
    //                     }
    //                 }

    //                 return $formattedTime;
    //             }
    //         }
    //     }

    //     return null;
    // }

    // protected function findAvailableDeliveryTime()
    // {
    //     $user = auth()->user();
    //     $customerId = $user->customer->id;

    //     $purchaseOrder = PurchaseOrder::where('customer_id', $customerId)
    //         ->where('status', 'complete')
    //         ->first();

    //     if (!$purchaseOrder) {
    //         return response()->json([
    //             'message' => 'there is no completed order'
    //         ], 404);
    //     }

    //     if ($purchaseOrder->is_paid !== 'paid') {
    //         return response()->json([
    //             'message' => 'you should pay before that'
    //         ], 400);
    //     }

    //     // $customerTimes = AvailableTime::where('customer_id', $customerId)
    //     //     ->pluck('available_at');
    //     $customerTimes = request()->input('delivery_time'); // إدخال الوقت من الـ API Request

    //     $companyAvailability = DeliveryCompanyAvailability::get()
    //         ->keyBy('day_of_week');

    //     $bookedTimes = PurchaseOrder::whereNotNull('delivery_time')
    //         ->pluck('delivery_time')
    //         ->map(fn($time) => Carbon::parse($time)->format('Y-m-d H:i'))
    //         ->toArray();

    //     foreach ($customerTimes as $time) {
    //         $carbonTime = Carbon::parse($time);
    //         $formattedTime = $carbonTime->format('Y-m-d H:i');
    //         $dayName = strtolower($carbonTime->format('l'));

    //         if (!$companyAvailability->has($dayName)) {
    //             continue;
    //         }

    //         $startTime = $companyAvailability[$dayName]->start_time;
    //         $endTime = $companyAvailability[$dayName]->end_time;
    //         $timeOnly = $carbonTime->format('H:i:s');

    //         if ($timeOnly >= $startTime && $timeOnly <= $endTime) {
    //             if (!in_array($formattedTime, $bookedTimes)) {

    //                 if ($user->userFcmTokens->exists()) {
    //                     try {
    //                         FirebaseNotification::setTitle('Delivery Appointment Scheduled')
    //                             ->setBody("A delivery appointment has been scheduled for your order: {$formattedTime}")
    //                             ->setUsers(collect([$user]))
    //                             ->setData([
    //                                 'order_id' => $purchaseOrder->id,
    //                                 'delivery_time' => $formattedTime,
    //                                 'type' => 'delivery_scheduled'
    //                             ])
    //                             ->push();
    //                     } catch (\Exception $e) {
    //                         Log::error('Failed to send delivery appointment notification', [
    //                             'user_id' => $user->id,
    //                             'error' => $e->getMessage(),
    //                         ]);
    //                     }
    //                 }
    //                 $purchaseOrder->update([
    //                     'delivery_time'   => $formattedTime,
    //                     'delivery_status' => 'confirmed',
    //                 ]);


    //                 return response()->json([
    //                     'message' => 'Done!',
    //                     'delivery_time' => $formattedTime
    //                 ]);
    //             }
    //         }
    //     }

    //     return response()->json([
    //         'message' => 'there is no time,please enter another available time!'
    //     ], 404);
    // }

    protected function findAvailableDeliveryTime()
{
    $user = auth()->user();
    $customerId = $user->customer->id;

    $purchaseOrder = PurchaseOrder::where('customer_id', $customerId)
        ->where('status', 'complete')
        ->first();

    if (!$purchaseOrder) {
        return response()->json([
            'message' => 'there is no completed order'
        ], 404);
    }

    if ($purchaseOrder->is_paid !== 'paid') {
        return response()->json([
            'message' => 'you should pay before that'
        ], 400);
    }

    // الحصول على قائمة الأوقات المتاحة التي أرسلها الزبون
    $customerTimes = request()->input('available_times');  // قائمة الأوقات المتاحة

    if (empty($customerTimes)) {
        return response()->json([
            'message' => 'Please provide a list of available times.'
        ], 400);
    }

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
                // عملية إرسال الإشعار وتحديث الحالة هنا...

                $purchaseOrder->update([
                    'delivery_time'   => $formattedTime,
                    'delivery_status' => 'confirmed',
                ]);

                return response()->json([
                    'message' => 'Done!',
                    'delivery_time' => $formattedTime
                ]);
            }
        }
    }

    return response()->json([
        'message' => 'No available time found from the list. Please try again.'
    ], 404);
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
                'message' => 'address not founnd',
                'delivery_price' => null,
                'supported' => false,
            ], 200);
        }


        $deliveryPrice = $placeCost->price;

        $customerId = auth()->user()->customer->id;
        $cartItems = \App\Models\Cart::where('customer_id', $customerId)->get();

        $totalCartPrice = $cartItems->sum('price');
        $totalWithDelivery = $totalCartPrice + $deliveryPrice;

        $depositAmount = $totalCartPrice * 0.5;
        $priceAfterDepositAndDelivery = $depositAmount + $deliveryPrice;

        return response()->json([
            'message' => 'Delivery price and total price with delivery retrieved successfully.',
            'delivery_price' => round($deliveryPrice, 2),
            'total_price_with_delivery' => round($totalWithDelivery, 2),
            'price_after_deposit_and_delivery' => round($priceAfterDepositAndDelivery, 2)
        ]);
    }

    public function payRemainingAmount($orderId)
    {
        $user = auth()->user();
        $order = PurchaseOrder::find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->is_paid === 'paid') {
            return response()->json(['message' => 'Order already fully paid'], 200);
        }

        $remainingAmount = $order->want_delivery === 'yes'
            ? $order->price_after_rabbon_with_delivery
            : $order->price_after_rabbon;

        $wallet = $user->wallets()->where('is_active', 1)->where('wallet_type', 'investment')->first();
        if (!$wallet) {
            return response()->json(['message' => 'No active wallet found'], 400);
        }

        if ($wallet->balance < $remainingAmount) {
            return response()->json(['message' => 'Insufficient balance to pay remaining amount'], 200);
        }

        $wallet->balance -= $remainingAmount;
        $wallet->save();

        $manager = GallaryManager::with('user.wallets')->first();
        if (!$manager || !$manager->user || !$manager->user->wallets->first()) {
            return response()->json(['message' => 'Manager wallet not found'], 500);
        }

        $managerWallet = $manager->user->wallets->first();
        $managerWallet->balance += $remainingAmount;
        $managerWallet->save();

        $order->update([
            'is_paid' => 'paid'
        ]);

        return response()->json([
            'message' => 'Remaining amount paid successfully!',
            'order'   => $order
        ]);
    }
}