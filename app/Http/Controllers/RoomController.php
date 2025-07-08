<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CustomizationItem;
use App\Models\Fabric;
use App\Models\FabricColor;
use App\Models\FabricType;
use App\Models\Item;
use App\Models\Rating;
use App\Models\Room;
use App\Models\RoomCustomization;
use App\Models\Wood;
use App\Models\WoodColor;
use App\Models\WoodType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    public function getRoomDefaults($roomId)
    {
        $room = Room::with([
            'items.itemDetail'
        ])->find($roomId);

        if (!$room) {
            return response()->json(['message' => 'Room not found'], 404);
        }

        $data = [
            'room_id' => $room->id,
            'room_name' => $room->name,
            'price' => $room->price,

            'wood_type'    => $room->wood_type,
            'wood_color'   => $room->wood_color,
            'fabric_type'  => $room->fabric_type,
            'fabric_color' => $room->fabric_color,

            'items' => []
        ];

        foreach ($room->items as $item) {
            $detail = $item->itemDetail->first();

            $data['items'][] = [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'wood_length' => $detail?->wood_length,
                'wood_width' => $detail?->wood_width,
                'wood_height' => $detail?->wood_height,
                'fabric_dimension' => $detail?->fabric_dimension,
            ];
        }

        return response()->json($data);
    }

    public function getAvailableWoodTypes($roomId)
    {
        $room = Room::with('roomDetail.wood')->find($roomId);
        if (!$room || !$room->roomDetail || !$room->roomDetail->wood) {
            return response()->json(['message' => 'No wood found'], 404);
        }

        $woodType = $room->roomDetail->wood->woodType;

        return response()->json([
            'wood_type_id' => $woodType->id,
            'type' => $woodType->name,
        ]);
    }

    public function getWoodColorsByType($woodTypeId)
    {
        $woods = Wood::with('WoodColor')
            ->where('wood_type_id', $woodTypeId)
            ->get();
        return $woods;

        $colors = $woods->map->WoodColor
            ->filter()
            ->unique('id')
            ->values()
            ->map(function ($color) {
                return [
                    'color_id' => $color->id,
                    'color' => $color->name
                ];
            });

        return response()->json($colors);
    }


    public function getAvailableFabricTypes($roomId)
    {
        $room = Room::with('roomDetail.fabric')->find($roomId);
        if (!$room || !$room->roomDetail || !$room->roomDetail->fabric) {
            return response()->json(['message' => 'No fabric found'], 404);
        }

        $fabricType = $room->roomDetail->fabric->fabricType;

        return response()->json([
            'id' => $fabricType->id,
            'type' => $fabricType->name,
        ]);
    }


    public function getFabricColorsByType($fabricTypeId)
    {
        $fabrics = Fabric::with('fabricColor')
            ->where('fabric_type_id', $fabricTypeId)
            ->get();

        $colorsWithPrices = $fabrics->map(function ($fabric) {
            return [
                'fabric_color_id' => $fabric->fabricColor->id,
                'color' => $fabric->fabricColor->name,
                'price_per_meter' => $fabric->price_per_meter,
            ];
        })
            ->unique('id')
            ->values();

        return response()->json($colorsWithPrices);
    }


    public function customizeRoom(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'wood_type' => 'nullable|string',
            'wood_color' => 'nullable|string',
            'fabric_type' => 'nullable|string',
            'fabric_color' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.new_length' => 'nullable|numeric',
            'items.*.new_width' => 'nullable|numeric',
            'items.*.new_height' => 'nullable|numeric',
        ]);

        $room = Room::with('items.itemDetail')->findOrFail($request->room_id);

        $oldWood = Wood::whereHas('woodType', function ($query) use ($room) {
            $query->where('name', $room->wood_type);
        })->whereHas('woodColor', function ($query) use ($room) {
            $query->where('name', $room->wood_color);
        })->first();

        $oldFabric = Fabric::whereHas('fabricType', function ($query) use ($room) {
            $query->where('name', $room->fabric_type);
        })->whereHas('fabricColor', function ($query) use ($room) {
            $query->where('name', $room->fabric_color);
        })->first();

        if (!$oldWood || !$oldFabric) {
            return response()->json(['message' => 'Old wood or fabric not found for room'], 400);
        }

        $newWoodTypeName = $request->wood_type ?? $room->wood_type;
        $newWoodColorName = $request->wood_color ?? $room->wood_color;
        $newFabricTypeName = $request->fabric_type ?? $room->fabric_type;
        $newFabricColorName = $request->fabric_color ?? $room->fabric_color;

        $newWood = Wood::whereHas('woodType', function ($query) use ($newWoodTypeName) {
            $query->where('name', $newWoodTypeName);
        })->whereHas('woodColor', function ($query) use ($newWoodColorName) {
            $query->where('name', $newWoodColorName);
        })->first();

        $newFabric = Fabric::whereHas('fabricType', function ($query) use ($newFabricTypeName) {
            $query->where('name', $newFabricTypeName);
        })->whereHas('fabricColor', function ($query) use ($newFabricColorName) {
            $query->where('name', $newFabricColorName);
        })->first();

        if (!$newWood || !$newFabric) {
            return response()->json(['message' => 'New wood or fabric not found'], 400);
        }

        $totalWoodArea = 0;
        $totalFabricArea = 0;
        foreach ($room->items as $item) {
            $detail = $item->itemDetail->first();
            if ($detail) {
                $totalWoodArea += $detail->wood_area_m2 ?? 0;
                $totalFabricArea += $detail->fabric_dimension ?? 0;
            }
        }

        $oldWoodCost = $totalWoodArea * $oldWood->price_per_meter;
        $newWoodCost = $totalWoodArea * $newWood->price_per_meter;

        $oldFabricCost = $totalFabricArea * $oldFabric->price_per_meter;
        $newFabricCost = $totalFabricArea * $newFabric->price_per_meter;

        $basePriceAfterMaterialChange = $room->price - $oldWoodCost - $oldFabricCost + $newWoodCost + $newFabricCost;

        $dimensionAdjustment = 0;
        $itemCustomizations = [];

        if ($request->has('items')) {
            foreach ($request->items as $itemInput) {
                $item = $room->items->where('id', $itemInput['item_id'])->first();
                if (!$item) continue;

                $detail = $item->itemDetail->first();
                if (!$detail) continue;

                $oldLength = $detail->wood_length ?? 0;
                $oldWidth = $detail->wood_width ?? 0;
                $oldHeight = $detail->wood_height ?? 0;

                $newLength = $itemInput['new_length'] ?? $oldLength;
                $newWidth = $itemInput['new_width'] ?? $oldWidth;
                $newHeight = $itemInput['new_height'] ?? $oldHeight;

                $diffLength = $newLength - $oldLength;
                $diffWidth = $newWidth - $oldWidth;
                $diffHeight = $newHeight - $oldHeight;

                $totalDiff = $diffLength + $diffWidth + $diffHeight;

                $itemAdjustment = $totalDiff * 0.10 * $item->price;

                $dimensionAdjustment += $itemAdjustment;

                $itemCustomizations[] = [
                    'item_id' => $item->id,
                    'new_length' => $newLength,
                    'new_width' => $newWidth,
                    'new_height' => $newHeight,
                ];
            }
        }

        $finalPrice = $basePriceAfterMaterialChange + $dimensionAdjustment;

        $minPrice = $room->price * 0.9;
        if ($finalPrice < $minPrice) {
            $finalPrice = $minPrice;
        }

        $customerId = auth()->user()->customer->id;

        $roomCustomization = RoomCustomization::create([
            'room_id' => $room->id,
            'customer_id' => $customerId,
            'wood_type_id' => $newWood->wood_type_id,
            'wood_color_id' => $newWood->woodColor->id,
            'fabric_type_id' => $newFabric->fabric_type_id,
            'fabric_color_id' => $newFabric->fabricColor->id,
            'final_price' => round($finalPrice, 2),
            'final_time' => $room->time,
        ]);

        foreach ($itemCustomizations as $itemCust) {
            CustomizationItem::create([
                'room_customization_id' => $roomCustomization->id,
                'item_id' => $itemCust['item_id'],
                'new_length' => $itemCust['new_length'],
                'new_width' => $itemCust['new_width'],
                'new_height' => $itemCust['new_height'],
            ]);
        }

        return response()->json([
            'message' => 'تم تخصيص الغرفة بنجاح',
            'room_customization' => $roomCustomization,
        ]);
    }


    public function showFurniture()
    {
        $response = Room::with(['items' => function ($q) {
            $q->withCount('likes')->withAvg('ratings', 'rate');
        }])
            ->withCount('likes')
            ->withAvg('ratings', 'rate')
            ->get()
            ->map(function ($room) {
                return [
                    'id' => $room->id,
                    'name' => $room->name,
                    'time' => (float)$room->items->sum('time'),
                    'price' => (float)$room->price,
                    'description' => $room->description,
                    'image_url' => $room->image_url,
                    'like_count' => $room->likes_count,
                    'average_rating' => (float) round($room->ratings_avg_rate ?? 0, 2),
                    'items' => $room->items->map(function ($item) {
                        $itemDetail = $item->itemDetail->first();
                        $wood = $itemDetail ? Wood::find($itemDetail->wood_id) : null;
                        $fabric = $itemDetail ? Fabric::find($itemDetail->fabric_id) : null;

                        return [
                            'id' => $item->id,
                            'name' => $item->name,
                            'time' => (float)$item->time,
                            'price' => (float)$item->price,
                            'image_url' => $item->image_url,
                            'wood_id' => optional($wood)->id,
                            'wood_name' => optional($wood)->name,
                            'wood_color' => optional($wood)->color,
                            'wood_price_per_meter' => (float)optional($wood)->price_per_meter,
                            'fabric_id' => optional($fabric)->id,
                            'fabric_name' => optional($fabric)->name,
                            'fabric_color' => optional($fabric)->color,
                            'fabric_price_per_meter' => (float)optional($fabric)->price_per_meter,
                        ];
                    }),
                ];
            });

        return response()->json(['allRooms' => $response], 200);
    }
    public function getRoomsByCategory($category_id)
    {
        $category = Category::with('rooms')->find($category_id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 200);
        }

        return response()->json([
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'rooms' => $category->rooms->map(function ($room) {
                    return [
                        'id' => $room->id,
                        'name' => $room->name,
                        'description' => $room->description,
                        'price' => $room->price,
                        'image_url' => $room->image_url,
                        'likes_count' => $room->likes()->count(),
                        'average_rating' => (float) round($room->ratings()->avg('rate'), 1),
                        'feedbacks' => $room->ratings->pluck('feedback')->filter()->values(),
                    ];
                })
            ]
        ], 200);
    }
    public function getRoomItems($room_id)
    {
        $room = Room::with([
            'items.itemDetail',
            'items.likes',
            'items.ratings'
        ])->find($room_id);

        if (!$room) {
            return response()->json(['message' => 'room not found'], 200);
        }

        return response()->json([
            'room' => [
                'id' => $room->id,
                'name' => $room->name,
                'description' => $room->description,
                'price' => $room->price,
                'items' => $room->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'price' => $item->price,
                        'time' => $item->time,
                        'wood_id' => optional($item->itemDetail)->wood_id,
                        'fabric_id' => optional($item->itemDetail)->fabric_id,
                        'wood_length' => optional($item->itemDetail)->wood_length,
                        'wood_width' => optional($item->itemDetail)->wood_width,
                        'wood_height' => optional($item->itemDetail)->wood_height,
                        'likes_count' => $item->likes->count(),
                        'average_rating' => (float) round($item->ratings->avg('rate'), 1),
                        'feedbacks' => $item->ratings->pluck('feedback')->filter()->values()
                    ];
                })
            ]
        ], 200);
    }
    public function getRoomDetails(Request $request, $room_id)
    {
        $customer = auth()->user()?->customer;

        $room = Room::with([
            'category',
            'items',
            'roomDetails.woods.types',
            'roomDetails.woods.colors',
            'roomDetails.fabrics.types',
            'roomDetails.fabrics.colors'
        ])->find($room_id);

        if (!$room) {
            return response()->json(['message' => 'Room not found'], 200);
        }

        $isFavorite = false;
        $isLiked = false;

        if ($customer) {
            $isFavorite = $customer->favorites()->where('room_id', $room->id)->exists();
            $isLiked = $customer->likes()->where('room_id', $room->id)->exists();
        }

        $allWoods = collect();
        $allFabrics = collect();

        foreach ($room->roomDetails as $detail) {
            $allWoods = $allWoods->merge(
                $detail->woods->map(function ($wood) {
                    return [
                        'id' => $wood->id,
                        'name' => $wood->name,
                        'price_per_meter' => $wood->price_per_meter,
                        'types' => $wood->types->map(fn($type) => [
                            'id' => $type->id,
                            'wood_id' => $type->wood_id,
                            'fabric_id' => $type->fabric_id,
                            'name' => $type->name ?? null,
                        ]),
                        'colors' => $wood->colors->map(fn($color) => [
                            'id' => $color->id,
                            'wood_id' => $color->wood_id,
                            'fabric_id' => $color->fabric_id,
                            'name' => $color->name,
                        ]),
                    ];
                })
            );

            $allFabrics = $allFabrics->merge(
                $detail->fabrics->map(function ($fabric) {
                    return [
                        'id' => $fabric->id,
                        'name' => $fabric->name,
                        'price_per_meter' => $fabric->price_per_meter,
                        'types' => $fabric->types->map(fn($type) => [
                            'id' => $type->id,
                            'name' => $type->name ?? null,
                            'wood_id' => $type->wood_id,
                            'fabric_id' => $type->fabric_id,
                        ]),
                        'colors' => $fabric->colors ? $fabric->colors->map(fn($color) => [
                            'id' => $color->id,
                            'wood_id' => $color->wood_id,
                            'fabric_id' => $color->fabric_id,
                            'name' => $color->name,
                        ]) : [],
                    ];
                })
            );
        }

        $allWoods = $allWoods->unique('id')->values();
        $allFabrics = $allFabrics->unique('id')->values();

        $ratings = Rating::with('customer.user')
            ->where('room_id', $room->id)
            ->get();

        $ratingData = $ratings->map(function ($rating) {
            return [
                'customer_name' => $rating->customer?->user?->name,
                'customer_image' => $rating->customer?->profile_image,
                'rate' => (float) $rating->rate,
                'feedback' => $rating->feedback,
            ];
        });

        $averageRating = (float) $ratings->avg('rate');
        $totalRate = $ratings->count();

        return response()->json([
            'room' => [
                'id' => $room->id,
                'name' => $room->name,
                'category_id' => $room->category_id,
                'category_name' => $room->category?->name,
                'description' => $room->description,
                'image_url' => $room->image_url,
                'count_reserved' => $room->count_reserved,
                'time' => $room->time,
                'price' => $room->price,
                'count' => $room->count,
                'is_favorite' => $isFavorite,
                'is_liked' => $isLiked,
                'likes_count' => $room->likes()->count(),
                'average_rating' => (float) $averageRating,
                'total_rate' => $totalRate,
                'items' => $room->items->map(fn($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'image_url' => $item->image_url,
                ]),
            ],
            'woods' => $allWoods,
            'fabrics' => $allFabrics,
            'ratings' => $ratingData,
        ]);
    }
    public function trendingRooms()
    {
        $rooms = Room::withCount(['roomOrder as total_sales' => function ($query) {
            $query->select(DB::raw("SUM(room_orders.count)"));
        }])
            ->orderByDesc('total_sales')
            ->take(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $rooms
        ]);
    }
    public function getRoomAfterCustomization($roomCustomizationId)
    {
        $baseCustomization = RoomCustomization::with('room.items')->find($roomCustomizationId);

        if (!$baseCustomization) {
            return response()->json(['message' => 'Room customization not found'], 200);
        }

        $room = $baseCustomization->room;

        $roomCustomizations = RoomCustomization::with('customizationItems.item')
            ->where('room_id', $room->id)
            ->get();

        if ($roomCustomizations->isEmpty()) {
            return response()->json(['message' => 'No customizations found for this room'], 200);
        }

        $customizationsData = $roomCustomizations->map(function ($customization) use ($room) {
            $itemsData = $room->items->map(function ($roomItem) use ($customization) {
                $customized = $customization->customizationItems->firstWhere('item_id', $roomItem->id);

                return [
                    'item_id' => $roomItem->id,
                    'item_name' => $roomItem->name,
                    'customized' => $customized ? true : false,
                    'final_price' => $customized ? $customized->final_price : $roomItem->price,
                    'final_time' => $customized ? $customized->final_time : $roomItem->time,
                ];
            });

            return [
                'room_customization_id' => $customization->id,
                'final_price' => $customization->final_price,
                'final_time' => $customization->final_time,
                'items' => $itemsData,
            ];
        });

        return response()->json([
            'message' => 'All room customizations retrieved successfully!',
            'room_id' => $room->id,
            'room_name' => $room->name,
            'customizations' => $customizationsData,
        ]);
    }
    // public function customizeRoom(Request $request, $roomId)
    // {
    //     $user = auth()->user()->customer;

    //     $room = Room::find($roomId);
    //     if (!$room) {
    //         return response()->json(['message' => 'Room not found'], 200);
    //     }

    //     if (!$request->has('items')) {
    //         return response()->json(['message' => 'Items data is required'], 200);
    //     }

    //     $itemsData = $request->input('items');
    //     $totalRoomPrice = 0;
    //     $totalRoomTime = 0;
    //     $itemPrices = [];
    //     $customizedItemIds = [];

    //     $roomCustomization = RoomCustomization::create([
    //         'room_id' => $roomId,
    //         'customer_id' => $user->id,
    //         'final_price' => 0,
    //         'final_time' => 0,
    //     ]);

    //     foreach ($itemsData as $customizationData) {
    //         $item = Item::find($customizationData['item_id']);
    //         if (!$item) {
    //             return response()->json(['message' => 'Item not found'], 200);
    //         }

    //         $finalPrice = $item->price;
    //         $finalTime = $item->time ?? 0;

    //         $newWood = isset($customizationData['wood_id']) ? Wood::find($customizationData['wood_id']) : null;
    //         $newFabric = isset($customizationData['fabric_id']) ? Fabric::find($customizationData['fabric_id']) : null;

    //         $extraLength = $customizationData['add_to_length'] ?? 0;
    //         $extraWidth = $customizationData['add_to_width'] ?? 0;
    //         $extraHeight = $customizationData['add_to_height'] ?? 0;

    //         $woodArea = 2 * (
    //             $item->itemDetail->wood_length * $item->itemDetail->wood_width +
    //             $item->itemDetail->wood_length * $item->itemDetail->wood_height +
    //             $item->itemDetail->wood_width * $item->itemDetail->wood_height
    //         );
    //         $woodAreaM2 = $woodArea / 10000;

    //         $newWoodPrice = $newWood ? $woodAreaM2 * $newWood->price_per_meter : 0;
    //         $newFabricPrice = $newFabric ? $item->itemDetail->fabric_dimension * $newFabric->price_per_meter : 0;

    //         $extraWoodCost = ($extraLength + $extraWidth + $extraHeight) * 0.1 * ($newWood ? $newWood->price_per_meter : 0);
    //         $extraFabricCost = ($extraLength + $extraWidth + $extraHeight) * 0.1 * ($newFabric ? $newFabric->price_per_meter : 0);

    //         $finalPrice += $newWoodPrice + $newFabricPrice + $extraWoodCost + $extraFabricCost;

    //         CustomizationItem::create([
    //             'room_customization_id' => $roomCustomization->id,
    //             'item_id' => $item->id,
    //             'wood_id' => $newWood?->id,
    //             'fabric_id' => $newFabric?->id,
    //             'wood_color' => $customizationData['wood_color'] ?? null,
    //             'fabric_color' => $customizationData['fabric_color'] ?? null,
    //             'add_to_length' => $extraLength,
    //             'add_to_width' => $extraWidth,
    //             'add_to_height' => $extraHeight,
    //             'final_price' => $finalPrice,
    //             'final_time' => $finalTime,
    //         ]);

    //         $itemPrices[] = [
    //             'item_id' => $item->id,
    //             'final_price' => number_format($finalPrice, 2, '.', ''),
    //             'customized' => true,
    //             'time' => $finalTime,
    //         ];

    //         $customizedItemIds[] = $item->id;

    //         $totalRoomPrice += $finalPrice;
    //         $totalRoomTime += $finalTime;
    //     }

    //     $roomItems = $room->items;

    //     foreach ($roomItems as $item) {
    //         if (!in_array($item->id, $customizedItemIds)) {
    //             $totalRoomTime += $item->time ?? 0;
    //             $totalRoomPrice += $item->price ?? 0;

    //             $itemPrices[] = [
    //                 'item_id' => $item->id,
    //                 'final_price' => number_format($item->price, 2, '.', ''),
    //                 'customized' => false,
    //                 'time' => $item->time ?? 0,
    //             ];
    //         }
    //     }

    //     $roomCustomization->update([
    //         'final_price' => number_format($totalRoomPrice, 2, '.', ''),
    //         'final_time' => $totalRoomTime + 10,
    //     ]);

    //     return response()->json([
    //         'message' => 'Room customized successfully!',
    //         'item_prices' => $itemPrices,
    //         'total_room_price' => number_format($totalRoomPrice, 2, '.', ''),
    //         'total_room_time' => $totalRoomTime + 10,
    //     ]);
    // }

    // public function getRoomCustomization($roomId)
    // {
    //     $user = auth()->user();
    //     if (!$user || !$user->customer) {
    //         return response()->json(['message' => 'login is required!'], 200);
    //     }

    //     $customerId = $user->customer->id;

    //     $roomCustomization = RoomCustomization::where('room_id', $roomId)
    //         ->where('customer_id', $customerId)
    //         ->with('customizedItems.item', 'customizedItems.customization')
    //         ->first();

    //     $room = Room::with('items')->find($roomId);

    //     if (!$room) {
    //         return response()->json(['message' => 'Room not found'], 200);
    //     }

    //     $items = $room->items->map(function ($item) use ($roomCustomization) {
    //         $customizedItem = optional($roomCustomization)->customizedItems
    //             ->where('item_id', $item->id)
    //             ->first();

    //         return [
    //             'item' => $item,
    //             'is_customized' => $customizedItem ? true : false,
    //             'customization' => $customizedItem ? $customizedItem->customization : null,
    //         ];
    //     });

    //     return response()->json([
    //         'room_id' => $room->id,
    //         'room_name' => $room->name,
    //         'items' => $items,
    //     ]);
    // }

    public function getRoomDefaultDetails(int $roomId)
    {
        $room = Room::find($roomId);

        if (!$room) {
            return response()->json(['message' => 'Room not found'], 404);
        }

        $roomDefaults = [
            'id' => $room->id,
            'name' => $room->name,
            'default_wood_type' => $room->wood_type,
            'default_wood_color' => $room->wood_color,
            'default_fabric_type' => $room->fabric_type,
            'default_fabric_color' => $room->fabric_color,
        ];

        return response()->json([
            'room_defaults' => $roomDefaults
        ]);
    }


    public function getRoomItemsWithOptions(int $roomId)
    {
        $room = Room::with([
            'items.itemDetail.wood.woodType',
            'items.itemDetail.wood.woodColor',
            'items.itemDetail.fabric.fabricType',
            'items.itemDetail.fabric.fabricColor',
        ])->find($roomId);

        if (!$room) {
            return response()->json(['message' => 'Room not found'], 404);
        }

        $itemsData = $room->items->map(function ($item) {
            $woodTypes = $item->itemDetail
                ->filter(fn($d) => $d->wood && $d->wood->woodType)
                ->pluck('wood.woodType')->unique('id')->values()
                ->map(fn($w) => ['id' => $w->id, 'name' => $w->name]);

            $woodColors = $item->itemDetail
                ->filter(fn($d) => $d->wood && $d->wood->woodColor)
                ->pluck('wood.woodColor')->unique('id')->values()
                ->map(fn($w) => ['id' => $w->id, 'name' => $w->name]);

            $fabricTypes = $item->itemDetail
                ->filter(fn($d) => $d->fabric && $d->fabric->fabricType)
                ->pluck('fabric.fabricType')->unique('id')->values()
                ->map(fn($f) => ['id' => $f->id, 'name' => $f->name]);

            $fabricColors = $item->itemDetail
                ->filter(fn($d) => $d->fabric && $d->fabric->fabricColor)
                ->pluck('fabric.fabricColor')->unique('id')->values()
                ->map(fn($f) => ['id' => $f->id, 'name' => $f->name]);

            return [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'image_url' => $item->image_url,
                'price' => $item->price,
                'wood_types' => $woodTypes,
                'wood_colors' => $woodColors,
                'fabric_types' => $fabricTypes,
                'fabric_colors' => $fabricColors,
                'details' => $item->itemDetail->map(function ($detail) {
                    return [
                        'wood_type' => $detail->wood?->woodType?->name,
                        'wood_color' => $detail->wood?->woodColor?->name,
                        'wood_dimensions' => [
                            'length' => $detail->wood_length,
                            'width' => $detail->wood_width,
                            'height' => $detail->wood_height,
                        ],
                        'fabric_type' => $detail->fabric?->fabricType?->name,
                        'fabric_color' => $detail->fabric?->fabricColor?->name,
                        'fabric_dimension' => $detail->fabric_dimension,
                    ];
                }),
            ];
        });

        return response()->json([
            'items' => $itemsData,
        ]);
    }


    // public function customizeRoom(Request $request, $roomId)
    // {
    //     $user = auth()->user()->customer;

    //     $room = Room::with('items.itemDetail')->find($roomId);
    //     if (!$room) {
    //         return response()->json(['message' => 'Room not found'], 404);
    //     }

    //     $itemsData = $request->input('items');
    //     if (!$itemsData || !is_array($itemsData)) {
    //         return response()->json(['message' => 'Items data is required and must be an array'], 422);
    //     }

    //     $roomCustomization = RoomCustomization::create([
    //         'room_id' => $roomId,
    //         'customer_id' => $user->id,
    //         'final_price' => 0,
    //         'final_time' => 0,
    //     ]);

    //     $totalRoomPrice = 0;
    //     $totalRoomTime = 0;
    //     $customizedItemIds = [];

    //     foreach ($itemsData as $customizationData) {
    //         $item = $room->items->where('id', $customizationData['item_id'])->first();
    //         if (!$item) {
    //             return response()->json(['message' => 'Item with ID ' . $customizationData['item_id'] . ' not found in room'], 404);
    //         }


    //         $finalPrice = $item->price;
    //         $finalTime = $item->time ?? 0;

    //         $itemDetail = $item->itemDetail->first();

    //         $newWood = isset($customizationData['wood_id']) ? Wood::find($customizationData['wood_id']) : null;
    //         $newFabric = isset($customizationData['fabric_id']) ? Fabric::find($customizationData['fabric_id']) : null;

    //         $extraLength = $customizationData['add_to_length'] ?? 0;
    //         $extraWidth = $customizationData['add_to_width'] ?? 0;
    //         $extraHeight = $customizationData['add_to_height'] ?? 0;

    //         $woodArea = 2 * (
    //             $itemDetail->wood_length * $itemDetail->wood_width +
    //             $itemDetail->wood_length * $itemDetail->wood_height +
    //             $itemDetail->wood_width * $itemDetail->wood_height
    //         );
    //         $woodAreaM2 = $woodArea / 10000;

    //         $newWoodPrice = $newWood ? $woodAreaM2 * $newWood->price_per_meter : 0;
    //         $newFabricPrice = $newFabric ? $itemDetail->fabric_dimension * $newFabric->price_per_meter : 0;

    //         $extraWoodCost = ($extraLength + $extraWidth + $extraHeight) * 0.1 * ($newWood ? $newWood->price_per_meter : 0);
    //         $extraFabricCost = ($extraLength + $extraWidth + $extraHeight) * 0.1 * ($newFabric ? $newFabric->price_per_meter : 0);

    //         $finalPrice += $newWoodPrice + $newFabricPrice + $extraWoodCost + $extraFabricCost;

    //         CustomizationItem::create([
    //             'room_customization_id' => $roomCustomization->id,
    //             'item_id' => $item->id,
    //             'wood_id' => $newWood?->id,
    //             'fabric_id' => $newFabric?->id,
    //             'wood_color' => $customizationData['wood_color'] ?? null,
    //             'fabric_color' => $customizationData['fabric_color'] ?? null,
    //             'add_to_length' => $extraLength,
    //             'add_to_width' => $extraWidth,
    //             'add_to_height' => $extraHeight,
    //             'final_price' => $finalPrice,
    //             'final_time' => $finalTime,
    //         ]);

    //         $customizedItemIds[] = $item->id;
    //         $totalRoomPrice += $finalPrice;
    //         $totalRoomTime += $finalTime;
    //     }

    //     foreach ($room->items as $item) {
    //         if (!in_array($item->id, $customizedItemIds)) {
    //             $totalRoomPrice += $item->price ?? 0;
    //             $totalRoomTime += $item->time ?? 0;
    //         }
    //     }

    //     $roomCustomization->update([
    //         'final_price' => number_format($totalRoomPrice, 2, '.', ''),
    //         'final_time' => $totalRoomTime + 10,
    //     ]);

    //     return response()->json([
    //         'message' => 'Room customized successfully!',
    //         'room_customization_id' => $roomCustomization->id,
    //         'total_room_price' => number_format($totalRoomPrice, 2, '.', ''),
    //         'total_room_time' => $totalRoomTime + 10,
    //     ]);
    // }
}
