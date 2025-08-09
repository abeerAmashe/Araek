<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Customization;
use App\Models\Fabric;
use App\Models\FabricType;
use App\Models\Item;
use App\Models\Wood;
use App\Models\WoodType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{

    public function getWoodTypesForItem($itemId)
    {
        $item = Item::with('itemDetail.wood.woodType')->find($itemId);

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $woodTypes = $item->itemDetail
            ->filter(fn($detail) => $detail->wood && $detail->wood->woodType)
            ->pluck('wood.woodType')
            ->unique('id')
            ->values()
            ->map(function ($woodType) {
                return [
                    'id' => $woodType->id,
                    'name' => $woodType->name,
                ];
            });

        return response()->json([
            'wood_types' => $woodTypes,
        ]);
    }

    public function getWoodColorsByType($woodTypeId)
    {
        $woods = Wood::with('woodColor')
            ->where('wood_type_id', $woodTypeId)
            ->get();

        $colors = $woods
            ->filter(fn($wood) => $wood->woodColor)
            ->groupBy('wood_color_id')
            ->map(function ($group) {
                $wood = $group->first();
                return [
                    'id' => $wood->woodColor->id,
                    'name' => $wood->woodColor->name,
                    'price_per_meter' => (float)$wood->woodType->price_per_meter ?? 0,
                ];
            })
            ->values();

        return response()->json([
            'wood_colors' => $colors,
        ]);
    }

    public function getFabricTypesByItem($itemId)
    {
        $item = Item::with('itemDetail.fabric.fabricType')->find($itemId);

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $fabrics = collect();

        foreach ($item->itemDetail as $detail) {
            if ($detail->fabric && $detail->fabric->fabricType) {
                $fabrics->push($detail->fabric);
            }
        }

        $fabricTypes = $fabrics
            ->groupBy('fabric_type_id')
            ->map(function ($group) {
                $fabric = $group->first();
                return [
                    'id' => $fabric->fabricType->id,
                    'name' => $fabric->fabricType->name,
                ];
            })
            ->values();

        return response()->json([
            'fabric_types' => $fabricTypes,
        ]);
    }

    public function getFabricColorsByType($fabricTypeId)
    {
        $fabrics = Fabric::with('fabricColor')
            ->where('fabric_type_id', $fabricTypeId)
            ->get();

        $colors = $fabrics
            ->filter(fn($fabric) => $fabric->fabricColor)
            ->groupBy('fabric_color_id')
            ->map(function ($group) {
                $fabric = $group->first();
                return [
                    'id' => $fabric->fabricColor->id,
                    'name' => $fabric->fabricColor->name,
                    'price_per_meter' => $fabric->fabricType->price_per_meter ?? 0,
                ];
            })
            ->values();

        return response()->json([
            'fabric_colors' => $colors,
        ]);
    }

    public function getItemCustomization($itemId)
    {
        $user = auth()->user();

        if (!$user || !$user->customer) {
            return response()->json(['message' => 'login is required!'], 200);
        }

        $item = Item::find($itemId);

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 200);
        }

        $customization = Customization::where('item_id', $itemId)
            ->where('customer_id', $user->customer->id)
            ->first();

        if ($customization) {
            return response()->json([
                'message' => 'Customization found',
                'customization' => $customization,
            ], 200);
        } else {
            return response()->json([
                'message' => 'No customization found for this item',
            ], 200);
        }
    }

    public function uploadGlb(Request $request, $id)
    {
        $item = Item::findOrFail($id);

        if (!$request->hasFile('glb_file')) {
            return response()->json(['error' => 'No GLB file uploaded'], 400);
        }

        $glbFile = $request->file('glb_file');

        if ($glbFile->getClientOriginalExtension() !== 'glb') {
            return response()->json(['error' => 'File must have .glb extension'], 400);
        }

        $glbFilename = 'item_' . $item->id . '.glb';
        $glbPath = $glbFile->storeAs('glb_models', $glbFilename, 'public');
        $glbUrl = asset('storage/glb_models/' . $glbFilename);

        $thumbnailUrl = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailFile = $request->file('thumbnail');

            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($thumbnailFile->getClientOriginalExtension(), $allowedExts)) {
                return response()->json(['error' => 'Invalid thumbnail image type'], 400);
            }

            $thumbFilename = 'item_' . $item->id . '_thumb.' . $thumbnailFile->getClientOriginalExtension();
            $thumbPath = $thumbnailFile->storeAs('thumbnails', $thumbFilename, 'public');
            $thumbnailUrl = asset('storage/thumbnails/' . $thumbFilename);
        }

        $item->glb_url = $glbUrl;
        if ($thumbnailUrl) {
            $item->thumbnail_url = $thumbnailUrl;
        }
        $item->save();

        return response()->json([
            'message' => 'GLB and thumbnail uploaded successfully',
            'glb_url' => $glbUrl,
            'thumbnail_url' => $thumbnailUrl
        ]);
    }

    public function getGlbItem($id)
    {
        $item = Item::with(['itemDetail.wood', 'itemDetail.fabric'])->findOrFail($id);
        $itemDetail = $item->itemDetail->first();

        return [[
            'id' => $item->id,
            'name' => $item->name,
            'prefabUrl' => $item->glb_url,
            'thumbnailUrl' => $item->thumbnail_url,
            'scale' => 1.0,
            'dimensions' => [
                isset($itemDetail->wood_length) ? $itemDetail->wood_length / 100 : null,  // Length in meters
                isset($itemDetail->wood_height) ? $itemDetail->wood_height / 100 : null,  // Height in meters
                isset($itemDetail->wood_width) ? $itemDetail->wood_width / 100 : null,    // Width in meters
            ],
        ]];
    }

    public function getAutoDetails($itemId)
    {
        $item = Item::find($itemId);

        if (!$item) {
            return [];
        }

        $details = $item->itemDetail()->get();

        $result = [];

        foreach ($details as $detail) {

            $wood = null;
            $woodTypeName = null;
            $woodColorName = null;
            if ($detail->wood_id) {
                $wood = \App\Models\Wood::find($detail->wood_id);
                if ($wood) {
                    $woodType = \App\Models\WoodType::find($wood->wood_type_id);
                    $woodColor = \App\Models\WoodColor::find($wood->wood_color_id);

                    $woodTypeName = $woodType?->name;
                    $woodColorName = $woodColor?->name;
                }
            }

            $fabric = null;
            $fabricTypeName = null;
            $fabricColorName = null;
            if ($detail->fabric_id) {
                $fabric = \App\Models\Fabric::find($detail->fabric_id);
                if ($fabric) {
                    $fabricType = \App\Models\FabricType::find($fabric->fabric_type_id);
                    $fabricColor = \App\Models\FabricColor::find($fabric->fabric_color_id);

                    $fabricTypeName = $fabricType?->name;
                    $fabricColorName = $fabricColor?->name;
                }
            }

            $result[] = [
                'wood' => [
                    'id' => $wood?->id,
                    'length' => $detail->wood_length,
                    'width' => $detail->wood_width,
                    'height' => $detail->wood_height,
                    'type' => $woodTypeName,
                    'color' => $woodColorName,
                ],
                'fabric' => [
                    'id' => $fabric?->id,
                    'dimension' => $detail->fabric_dimension,
                    'type' => $fabricTypeName,
                    'color' => $fabricColorName,
                ],
            ];
        }

        return $result;
    }

    public function getItemDetails($itemId)
    {
        $item = Item::with('itemDetail')->find($itemId);

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $woods = collect();
        $fabrics = collect();

        foreach ($item->itemDetail as $detail) {
            if ($detail->wood_id) {
                $wood = \App\Models\Wood::with(['WoodColor', 'WoodType'])->find($detail->wood_id);
                if ($wood) {
                    $woods->push([
                        'id' => $wood->id,
                        'name' => $wood->name,
                        'price_per_meter' => $wood->price_per_meter ?? null,
                        'color' => $wood->WoodColor ? [
                            'id' => $wood->WoodColor->id,
                            'name' => $wood->WoodColor->name,
                        ] : null,
                        'type' => $wood->WoodType ? [
                            'id' => $wood->WoodType->id,
                            'name' => $wood->WoodType->name,
                            'price_per_meter' => $wood->WoodType->price_per_meter,
                        ] : null,
                    ]);
                }
            }

            if ($detail->fabric_id) {
                $fabric = \App\Models\Fabric::with(['fabricColor', 'fabricType'])->find($detail->fabric_id);
                if ($fabric) {
                    $fabrics->push([
                        'id' => $fabric->id,
                        'name' => $fabric->name,
                        'price_per_meter' => $fabric->price_per_meter ?? null,
                        'color' => $fabric->fabricColor ? [
                            'id' => $fabric->fabricColor->id,
                            'name' => $fabric->fabricColor->name,
                        ] : null,
                        'type' => $fabric->fabricType ? [
                            'id' => $fabric->fabricType->id,
                            'name' => $fabric->fabricType->name,
                            'price_per_meter' => $fabric->fabricType->price_per_meter,
                        ] : null,
                    ]);
                }
            }
        }

        $averageRating = (float) $item->ratings()->avg('rate');
        $ratings = $item->ratings->map(function ($rating) {
            return [
                'feedback' => $rating->feedback,
                'rate' => (float) $rating->rate,
                'customer' => [
                    'id' => $rating->customer->id,
                    'name' => $rating->customer->user->name ?? null,
                    'image_url' => $rating->customer->user->image_url ?? null,
                ],
            ];
        });

        $userId = auth()->id();
        $customer = $userId ? \App\Models\Customer::where('user_id', $userId)->first() : null;
        $customerId = $customer ? $customer->id : null;

        $isLiked = $customerId
            ? \App\Models\Like::where('item_id', $itemId)->where('customer_id', $customerId)->exists()
            : false;

        $isFavorite = $customerId
            ? \App\Models\Favorite::where('item_id', $itemId)->where('customer_id', $customerId)->exists()
            : false;

        $likeCounts = \App\Models\Like::where('item_id', $itemId)->count();

        $response = [
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'image_url' => $item->image_url,
                'description' => $item->description,
                'price' => $item->price,
                'time' => $item->time,
                'count' => $item->count,
                'count_reserved' => $item->count_reserved,
            ],

            'item_details' => $item->itemDetail->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'wood_length' => $detail->wood_length,
                    'wood_width' => $detail->wood_width,
                    'wood_height' => $detail->wood_height,
                    'fabric_length' => $detail->fabric_length,
                    'fabric_width' => $detail->fabric_width,
                    'fabric_dimension' => $detail->fabric_dimension,
                    'wood_area_m2' => $detail->wood_area_m2,
                ];
            }),

            'woods' => $woods->values(),
            'fabrics' => $fabrics->values(),

            'average_rating' => round($averageRating, 2),
            'ratings' => $ratings,
            'is_liked' => $isLiked,
            'is_favorite' => $isFavorite,
            'like_counts' => $likeCounts,
        ];


        $firstWood = $woods->first();
        $firstFabric = $fabrics->first();

        $response['wood_color'] = $firstWood['color']['name'] ?? null;
        $response['wood_type'] = $firstWood['type']['name'] ?? null;
        $response['fabric_color'] = $firstFabric['color']['name'] ?? null;
        $response['fabric_type'] = $firstFabric['type']['name'] ?? null;

        return response()->json($response);
    }

    public function customizeItem(Request $request, Item $item)
    {
        $request->validate([
            'wood_id' => 'nullable|exists:woods,id',
            'fabric_id' => 'nullable|exists:fabrics,id',
            'wood_type_id' => 'nullable|exists:wood_types,id',
            'wood_color_id' => 'nullable|exists:wood_colors,id',
            'fabric_type_id' => 'nullable|exists:fabric_types,id',
            'fabric_color_id' => 'nullable|exists:fabric_colors,id',
            'new_length' => 'nullable|numeric',
            'new_width' => 'nullable|numeric',
            'new_height' => 'nullable|numeric',
        ]);

        $item->load('itemDetail.wood.woodType', 'itemDetail.fabric.fabricType');
        $itemDetail = $item->itemDetail->first();

        if (!$itemDetail) {
            return response()->json(['message' => 'Item detail not found'], 404);
        }

        $originalWood = $itemDetail->wood;
        $originalFabric = $itemDetail->fabric;
        $originalWoodType = $originalWood->woodType ?? null;
        $originalFabricType = $originalFabric->fabricType ?? null;
        $originalWoodArea = $itemDetail->wood_area_m2 ?? 0;
        $originalFabricLength = $itemDetail->fabric_length ?? 0;
        $originalFabricWidth = $itemDetail->fabric_width ?? 0;
        $originalPrice = $item->price;

        $finalPrice = $originalPrice;

        if ($request->wood_type_id && $originalWoodType && $request->wood_type_id != $originalWoodType->id) {
            $newWoodType = \App\Models\WoodType::find($request->wood_type_id);
            $oldWoodCost = $originalWoodArea * ($originalWoodType->price_per_meter ?? 0);
            $newWoodCost = $originalWoodArea * ($newWoodType->price_per_meter ?? 0);
            $finalPrice = $finalPrice - $oldWoodCost + $newWoodCost;
        }

        if ($request->fabric_type_id && $originalFabricType && $request->fabric_type_id != $originalFabricType->id) {
            $fabricArea = ($originalFabricLength / 100) * ($originalFabricWidth / 100); // m²
            $oldFabricCost = $fabricArea * ($originalFabricType->price_per_meter ?? 0);
            $newFabricType = \App\Models\FabricType::find($request->fabric_type_id);
            $newFabricCost = $fabricArea * ($newFabricType->price_per_meter ?? 0);
            $finalPrice = $finalPrice - $oldFabricCost + $newFabricCost;
        }

        $originalLength = $itemDetail->wood_length ?? 0;
        $originalWidth = $itemDetail->wood_width ?? 0;
        $originalHeight = $itemDetail->wood_height ?? 0;

        $newLength = $request->new_length ?? $originalLength;
        $newWidth = $request->new_width ?? $originalWidth;
        $newHeight = $request->new_height ?? $originalHeight;

        $lengthDifference = $newLength - $originalLength;
        $widthDifference = $newWidth - $originalWidth;
        $heightDifference = $newHeight - $originalHeight;

        if ($lengthDifference != 0) {
            $percentageChange = ($lengthDifference / 100) * 0.10;
            $change = $percentageChange * $originalPrice;
            $finalPrice += $change;
        }

        if ($widthDifference != 0) {
            $percentageChange = ($widthDifference / 100) * 0.10;
            $change = $percentageChange * $originalPrice;
            $finalPrice += $change;
        }

        if ($heightDifference != 0) {
            $percentageChange = ($heightDifference / 100) * 0.10;
            $change = $percentageChange * $originalPrice;
            $finalPrice += $change;
        }

        $minimumAllowedPrice = $originalPrice * 0.9;
        if ($finalPrice < $minimumAllowedPrice) {
            $finalPrice = $minimumAllowedPrice;
        }

        $newFabricLength = $originalFabricLength + $lengthDifference + $heightDifference;
        $newFabricWidth  = $originalFabricWidth  + $widthDifference;

        $newFabricLength = max($newFabricLength, 0);
        $newFabricWidth  = max($newFabricWidth, 0);

        $finalTime = $item->time + 5;

        $customerId = auth()->user()->customer->id;

        Customization::create([
            'item_id' => $item->id,
            'wood_id' => $request->wood_id ?? $originalWood->id,
            'wood_type_id' => $request->wood_type_id ?? $originalWoodType?->id,
            'wood_color_id' => $request->wood_color_id ?? $originalWood->wood_color_id,
            'fabric_id' => $request->fabric_id ?? $originalFabric->id,
            'fabric_type_id' => $request->fabric_type_id ?? $originalFabricType?->id,
            'fabric_color_id' => $request->fabric_color_id ?? $originalFabric->fabric_color_id,
            'new_length' => $newLength,
            'new_width' => $newWidth,
            'new_height' => $newHeight,
            'old_price' => $originalPrice,
            'final_price' => $finalPrice,
            'final_time' => $finalTime,
            'wood_color' => $request->wood_color_id ?? $originalWood->wood_color_id,
            'fabric_color' => $request->fabric_color_id ?? $originalFabric->fabric_color_id,
            'fabric_length' => $newFabricLength,
            'fabric_width' => $newFabricWidth,
            'customer_id' => $customerId,
        ]);

        return response()->json([
            'message' => 'Customization created successfully.',
            'orginal_price' => $originalPrice,
            'final_price' => $finalPrice,
            'final_time' => $finalTime,
            'fabric_length' => $newFabricLength,
            'fabric_width' => $newFabricWidth,
        ]);
    }

    public function trendingItems()
    {
        $items = Item::with('room')
            ->withCount(['purchaseOrder as total_sales' => function ($query) {
                $query->select(DB::raw("SUM(item_orders.count)"));
            }])
            ->orderByDesc('total_sales')
            ->take(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $items
        ]);
    }

    public function handleCustomizationResponse(Request $request, $itemId)
    {
        $user = auth()->user();

        if (!$user || !$user->customer) {
            return response()->json(['message' => 'login is required!'], 200);
        }

        $customerId = $user->customer->id;

        $request->validate([
            'action' => 'required|string|in:accept,reject',
        ]);

        $customization = \App\Models\Customization::where('item_id', $itemId)
            ->where('customer_id', $customerId)
            ->first();

        if (!$customization) {
            return response()->json(['message' => 'there is no customize'], 200);
        }

        if ($request->action === 'accept') {
            return response()->json([
                'message' => 'Done ^_^',
                'customization' => $customization,
            ]);
        }

        if ($request->action === 'reject') {
            $customization->delete();

            return response()->json([
                'message' => 'Done ^_^',
            ]);
        }
    }

    public function getItemCustomizationOptions($itemId)
    {
        $item = Item::with([
            'itemDetail.itemWoods.wood.colors',
            'itemDetail.itemWoods.wood.types',
            'itemDetail.itemFabrics.fabric.colors',
            'itemDetail.itemFabrics.fabric.types',
        ])->find($itemId);

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $defaultWood = [
            'color' => $item->wood_color,
            'type' => $item->wood_type,
        ];

        $defaultFabric = [
            'color' => $item->fabric_color,
            'type' => $item->fabric_type,
        ];

        $woodOptions = collect();
        $fabricOptions = collect();

        foreach ($item->itemDetail as $detail) {
            foreach ($detail->itemWoods as $itemWood) {
                foreach ($itemWood->wood as $wood) {
                    $woodOptions->push([
                        'wood_id' => $wood->id,
                        'wood_name' => $wood->name,
                        'colors' => $wood->colors->map(fn($color) => [
                            'id' => $color->id,
                            'name' => $color->name,
                        ]),
                        'types' => $wood->types->map(fn($type) => [
                            'id' => $type->id,
                            'name' => $type->name,
                        ]),
                    ]);
                }
            }

            foreach ($detail->itemFabrics as $itemFabric) {
                foreach ($itemFabric->fabric as $fabric) {
                    $fabricOptions->push([
                        'fabric_id' => $fabric->id,
                        'fabric_name' => $fabric->name,
                        'colors' => $fabric->colors->map(fn($color) => [
                            'id' => $color->id,
                            'name' => $color->name,
                        ]),
                        'types' => $fabric->types->map(fn($type) => [
                            'id' => $type->id,
                            'name' => $type->name,
                        ]),
                    ]);
                }
            }
        }

        return response()->json([
            'default_wood' => $defaultWood,
            'default_fabric' => $defaultFabric,
            'available_woods' => $woodOptions->unique('wood_id')->values(),
            'available_fabrics' => $fabricOptions->unique('fabric_id')->values(),
        ]);
    }
}