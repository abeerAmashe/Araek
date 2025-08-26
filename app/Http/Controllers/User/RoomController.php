<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CustomizationItem;
use App\Models\Fabric;
use App\Models\FabricColor;
use App\Models\FabricType;
use App\Models\Favorite;
use App\Models\Item;
use App\Models\Rating;
use App\Models\Room;
use App\Models\RoomCustomization;
use App\Models\Wood;
use App\Models\WoodColor;
use App\Models\WoodType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;



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
        $room = Room::with(
            'roomDetail.wood',

        )->find($roomId);
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

    public function customizeRoom(Request $request, $roomId)
    {
        $validator = Validator::make($request->all(), [
            'wood_color_id'       => 'nullable|integer|exists:wood_colors,id',
            'wood_type_id'        => 'nullable|integer|exists:wood_types,id',
            'fabric_color_id'     => 'nullable|integer|exists:fabric_colors,id',
            'fabric_type_id'      => 'nullable|integer|exists:fabric_types,id',
            'items'               => 'nullable|array',
            'items.*.item_id'     => 'required|integer|exists:items,id',
            'items.*.new_length'  => 'nullable|numeric|min:0',
            'items.*.new_width'   => 'nullable|numeric|min:0',
            'items.*.new_height'  => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }
        if (!$roomId || !is_numeric($roomId)) {
            return response()->json([
                'message' => 'Room Not Found',
            ]);
        }

        $room = Room::with([
            'items.itemDetail.wood.woodType',
            'items.itemDetail.fabric.fabricType'
        ])->find($roomId);

        if (!$room) {
            return response()->json(['message' => 'Room not found'], 404);
        }

        $customerId = auth()->user()->customer->id;

        $customization = RoomCustomization::create([
            'room_id'         => $room->id,
            'customer_id'     => $customerId,
            'wood_color_id'   => $request->wood_color_id ?? $room->wood_color_id,
            'wood_type_id'    => $request->wood_type_id ?? $room->wood_type_id,
            'fabric_color_id' => $request->fabric_color_id ?? $room->fabric_color_id,
            'fabric_type_id'  => $request->fabric_type_id ?? $room->fabric_type_id,
            'final_price'     => 0,
            'final_time'      => $room->time + 5,
        ]);

        $originalRoomPrice = $room->price;
        $finalRoomPrice = $originalRoomPrice;

        $roomWoodRate = \App\Models\WoodType::find($room->wood_type_id)?->price_per_meter ?? 0;
        $newWoodRate = $request->wood_type_id
            ? \App\Models\WoodType::find($request->wood_type_id)?->price_per_meter ?? $roomWoodRate
            : $roomWoodRate;

        $itemsInput = collect($request->input('items', []))->keyBy('item_id');

        foreach ($room->items as $item) {
            $detail = $item->itemDetail->first();
            if (!$detail) continue;

            $input = $itemsInput->get($item->id, []);

            $originalLength = $detail->wood_length ?? 0;
            $originalWidth  = $detail->wood_width ?? 0;
            $originalHeight = $detail->wood_height ?? 0;
            $originalPrice  = $item->price;

            $newLength = $input['new_length'] ?? $originalLength;
            $newWidth  = $input['new_width']  ?? $originalWidth;
            $newHeight = $input['new_height'] ?? $originalHeight;

            $lenDiff = $newLength - $originalLength;
            $widDiff = $newWidth  - $originalWidth;
            $heiDiff = $newHeight - $originalHeight;

            $newItemPrice = $originalPrice;

            if ($lenDiff != 0) $newItemPrice += ($lenDiff / 100) * 0.10 * $originalPrice;
            if ($widDiff != 0) $newItemPrice += ($widDiff / 100) * 0.10 * $originalPrice;
            if ($heiDiff != 0) $newItemPrice += ($heiDiff / 100) * 0.10 * $originalPrice;

            $woodArea = $detail->wood_area_m2 ?? 0;
            $oldWoodRate = $roomWoodRate;

            if (abs($newWoodRate - $oldWoodRate) > 0.01 && $woodArea > 0) {
                $newItemPrice = $newItemPrice - ($woodArea * $oldWoodRate) + ($woodArea * $newWoodRate);
            }

            $fabLength = $detail->fabric_length ?? 0;
            $fabWidth  = $detail->fabric_width ?? 0;
            $fabArea = ($fabLength / 100) * ($fabWidth / 100);
            $oldFabRate = \App\Models\FabricType::find($room->fabric_type_id)?->price_per_meter ?? 0;
            $newFabRate = $request->fabric_type_id
                ? \App\Models\FabricType::find($request->fabric_type_id)?->price_per_meter ?? $oldFabRate
                : $oldFabRate;

            if (abs($newFabRate - $oldFabRate) > 0.01 && $fabArea > 0) {
                $newItemPrice = $newItemPrice - ($fabArea * $oldFabRate) + ($fabArea * $newFabRate);
            }

            $newFabLength = $fabLength + $lenDiff + $heiDiff;
            $newFabWidth  = $fabWidth  + $widDiff;

            $newFabLength = max($newFabLength, 0);
            $newFabWidth  = max($newFabWidth, 0);

            $minAllowed = $originalPrice * 0.9;
            $newItemPrice = max($newItemPrice, $minAllowed);

            $finalRoomPrice = $finalRoomPrice - $originalPrice + $newItemPrice;

            CustomizationItem::create([
                'room_customization_id' => $customization->id,
                'item_id'               => $item->id,
                'new_length'            => $newLength,
                'new_width'             => $newWidth,
                'new_height'            => $newHeight,
                'fabric_length'         => $newFabLength,
                'fabric_width'          => $newFabWidth,
            ]);
        }

        $minRoomPrice = $originalRoomPrice * 0.9;
        $customization->final_price = max($finalRoomPrice, $minRoomPrice);
        $customization->save();

        return response()->json([
            'message'          => 'Room customized successfully',
            'customization_id' => $customization->id,
            'original_price'   => $originalRoomPrice,
            'final_price'      => $customization->final_price,
            'final_time'       => $customization->final_time,
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


    public function getRoomDetails(Request $request, $id)
    {
        $room = Room::with([
            'category',
            'roomDetail.wood',
            'roomDetail.fabric',
            'ratings.customer.user',
            'items',
            'favorites',
            'likes',
        ])->findOrFail($id);

        $allWoods = collect();
        $allFabrics = collect();

        if ($room->roomDetail) {
            $wood = $room->roomDetail->wood;
            if ($wood) {
                $allWoods->push([
                    'id' => $wood->id,
                    'name' => $wood->name,
                    'price_per_meter' => $wood->price_per_meter,
                    'types' => $wood->woodType ? [[
                        'id' => $wood->woodType->id,
                        'name' => $wood->woodType->name,
                        'price_per_meter' => $wood->woodType->price_per_meter,
                    ]] : [],
                    'colors' => $wood->woodColor ? [[
                        'id' => $wood->woodColor->id,
                        'name' => $wood->woodColor->name,
                    ]] : [],
                ]);
            }

            $fabric = $room->roomDetail->fabric;
            if ($fabric) {
                $allFabrics->push([
                    'id' => $fabric->id,
                    'name' => $fabric->name,
                    'price_per_meter' => $fabric->price_per_meter,
                    'types' => $fabric->fabricType ? [[
                        'id' => $fabric->fabricType->id,
                        'name' => $fabric->fabricType->name,
                        'price_per_meter' => $fabric->fabricType->price_per_meter,
                    ]] : [],
                    'colors' => $fabric->fabricColor ? [[
                        'id' => $fabric->fabricColor->id,
                        'name' => $fabric->fabricColor->name,
                    ]] : [],
                ]);
            }
        }

        $user = auth()->user();
        $customer = $user ? $user->customer : null;
        $customerId = $customer ? $customer->id : null;

        $isFavorited = $customerId
            ? Favorite::where('customer_id', $customerId)->where('room_id', $room->id)->exists()
            : false;

        $isLiked = $customerId
            ? \App\Models\Like::where('room_id', $room->id)->where('customer_id', $customerId)->exists()
            : false;

        $likeCounts = \App\Models\Like::where('room_id', $room->id)->count();

        $averageRating = round((float) $room->ratings()->avg('rate'), 1);
        $totalRate = $room->ratings()->count();

        $ratings = $room->ratings->map(function ($rating) {
            return [
                'customer_name' => $rating->customer->user->name ?? null,
                'customer_image' => $rating->customer->user->image_url ?? null,
                'rate' => (float) $rating->rate,
                'feedback' => $rating->feedback,
            ];
        });

        $items = $room->items->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'price' => $item->price,
                'image_url' => $item->image_url,
            ];
        });

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
                'is_favorited' => $isFavorited,
                'is_liked' => $isLiked,
                'likes_count' => $likeCounts,
                'average_rating' => $averageRating,
                'total_rate' => $totalRate,
                'items' => $items,
            ],
            'woods' => $allWoods->values(),
            'fabrics' => $allFabrics->values(),
            'ratings' => $ratings,
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
}