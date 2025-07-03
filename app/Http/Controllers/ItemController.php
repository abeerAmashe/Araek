<?php

namespace App\Http\Controllers;

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

    public function getItemDetails($itemId)
    {
        $item = Item::with([
            'itemDetail.itemWoods.wood.colors',
            'itemDetail.itemWoods.wood.types',
            'itemDetail.itemFabrics.fabric.colors',
            'itemDetail.itemFabrics.fabric.types',
            'ratings.customer'
        ])->where('id', $itemId)->first();

        if (!$item) {
            return response()->json(['message' => 'Item not found']);
        }

        $averageRating = (float) $item->ratings()->avg('rate');

        $ratings = $item->ratings->map(function ($rating) {
            return [
                'feedback' => $rating->feedback,
                'rate' => (float) $rating->rate,
                'customer' => [
                    'id' => $rating->customer->id,
                    'name' => $rating->customer->name,
                    'image_url' => $rating->customer->image_url ?? null,
                ],
            ];
        });

        $userId = auth()->id();
        $customer = $userId ? \App\Models\Customer::where('user_id', $userId)->first() : null;

        $customerId = $customer ? $customer->id : null;

        // Debug:
        // dd($customerId, $itemId);

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
                'wood_color' => $item->wood_color,
                'wood_type' => $item->wood_type,
                'fabric_color' => $item->fabric_color,
                'fabric_type' => $item->fabric_type,
            ],

            'item_details' => $item->itemDetail,
            'average_rating' => (float) round($averageRating, 2),
            'ratings' => $ratings,
            'is_liked' => $isLiked,
            'is_favorite' => $isFavorite,
            'like_counts' => $likeCounts,
        ];

        return response()->json($response);
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

    // public function customizeItem(Request $request, Item $item)
    // {

    //     $user = auth()->user()->customer;

    //     if (!$item) {
    //         return response()->json(['message' => 'item not found'], 200);
    //     }

    //     $itemDetail = $item->itemDetail;
    //     if (!$itemDetail) {
    //         return response()->json(['message' => 'item detail not found'], 200);
    //     }

    //     $existingCustomization = Customization::where('item_id', $item->id)
    //         ->where('customer_id', $user->id)
    //         ->first();

    //     $oldWood = Wood::find($itemDetail->wood_id);
    //     $newWood = $request->wood_id ? Wood::find($request->wood_id) : null;

    //     if ($request->wood_id && !$newWood) {
    //         return response()->json(['message' => 'wood type not found'], 200);
    //     }

    //     $oldFabric = Fabric::find($itemDetail->fabric_id);
    //     $newFabric = $request->fabric_id ? Fabric::find($request->fabric_id) : null;

    //     if ($request->fabric_id && !$newFabric) {
    //         return response()->json(['message' => 'fabric type not found'], 200);
    //     }

    //     $new_wood_Color = $request->wood_color ?? null;
    //     $new_fabric_Color = $request->fabric_color ?? null;

    //     $woodArea = 2 * ($itemDetail->wood_length * $itemDetail->wood_width
    //         + $itemDetail->wood_length * $itemDetail->wood_height
    //         + $itemDetail->wood_width * $itemDetail->wood_height);

    //     $woodAreaM2 = $woodArea / 10_000;

    //     $oldWoodPrice = $oldWood ? $woodAreaM2 * $oldWood->price_per_meter : 0;
    //     $newWoodPrice = $newWood ? $woodAreaM2 * $newWood->price_per_meter : 0;

    //     $oldFabricPrice = $oldFabric ? ($itemDetail->fabric_dimension) * $oldFabric->price_per_meter : 0;
    //     $newFabricPrice = $newFabric ? ($itemDetail->fabric_dimension) * $newFabric->price_per_meter : 0;

    //     $extraLength = $request->add_to_length ?? 0;
    //     $extraWidth = $request->add_to_width ?? 0;
    //     $extraHeight = $request->add_to_height ?? 0;

    //     $extraWoodCost = ($extraLength + $extraWidth + $extraHeight) * 0.1 * ($newWood ? $newWood->price_per_meter : $oldWood->price_per_meter);
    //     $extraFabricCost = ($extraLength + $extraWidth + $extraHeight) * 0.1 * ($newFabric ? $newFabric->price_per_meter : $oldFabric->price_per_meter);

    //     $finalPrice = $item->price;
    //     if ($newWood) {
    //         $finalPrice = $finalPrice - $oldWoodPrice + $newWoodPrice;
    //     }

    //     if ($newFabric) {
    //         $finalPrice = $finalPrice - $oldFabricPrice + $newFabricPrice;
    //     }

    //     $finalPrice = $finalPrice + $extraWoodCost + $extraFabricCost;

    //     $originalTime = $itemDetail->time;
    //     $finalTime = $originalTime + 5;

    //     if ($existingCustomization) {
    //         $existingCustomization->update([
    //             'wood_id' => $newWood ? $newWood->id : $existingCustomization->wood_id,
    //             'fabric_id' => $newFabric ? $newFabric->id : $existingCustomization->fabric_id,
    //             'extra_length' => $extraLength,
    //             'extra_width' => $extraWidth,
    //             'extra_height' => $extraHeight,
    //             'final_price' => $finalPrice,
    //             'wood_color' => $new_wood_Color ?? $existingCustomization->wood_color,
    //             'fabric_color' => $new_fabric_Color ?? $existingCustomization->fabric_color,

    //         ]);

    //         $customization = $existingCustomization;
    //     } else {
    //         $customization = Customization::create([
    //             'item_id' => $item->id,
    //             'customer_id' => $user->id,
    //             'wood_id' => $newWood ? $newWood->id : null,
    //             'fabric_id' => $newFabric ? $newFabric->id : null,
    //             'extra_length' => $extraLength,
    //             'extra_width' => $extraWidth,
    //             'extra_height' => $extraHeight,
    //             'old_price' => $item->price,
    //             'final_price' => $finalPrice,
    //             'new_wood_color' => $new_wood_Color,
    //             'new_fabric_color' => $new_fabric_Color
    //         ]);
    //     }

    //     return response()->json([
    //         'message' => 'Done ^_^',
    //         'customization' => $customization,
    //         'final_time' => $finalTime,
    //         'customization_id' => $customization->id,
    //     ]);
    // }
    public function customizeItem(Request $request, Item $item)
    {
        $user = auth()->user()->customer;

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $itemDetails = $item->itemDetail;
        if ($itemDetails->isEmpty()) {
            return response()->json(['message' => 'Item detail not found'], 404);
        }

        $existingCustomization = Customization::where('item_id', $item->id)
            ->where('customer_id', $user->id)
            ->first();

        $allowedFabricIds = $itemDetails->pluck('fabric_id')->filter()->unique()->toArray();
        $allowedWoodIds = $itemDetails->pluck('wood_id')->filter()->unique()->toArray();

        if ($request->wood_id && !in_array($request->wood_id, $allowedWoodIds)) {
            return response()->json(['message' => 'Selected wood is not available for this item'], 422);
        }
        $newWood = $request->wood_id ? Wood::find($request->wood_id) : null;
        if ($request->wood_id && !$newWood) {
            return response()->json(['message' => 'Wood type not found'], 404);
        }

        if ($request->fabric_id && !in_array($request->fabric_id, $allowedFabricIds)) {
            return response()->json(['message' => 'Selected fabric is not available for this item'], 422);
        }
        $newFabric = $request->fabric_id ? Fabric::find($request->fabric_id) : null;
        if ($request->fabric_id && !$newFabric) {
            return response()->json(['message' => 'Fabric type not found'], 404);
        }

        if ($request->fabric_color && $newFabric) {
            $allowedFabricColorIds = Fabric::where('fabric_type_id', $newFabric->fabric_type_id)
                ->pluck('fabric_color_id')
                ->filter()
                ->unique()
                ->toArray();

            if (!in_array($request->fabric_color, $allowedFabricColorIds)) {
                return response()->json(['message' => 'Selected fabric color is not available for this fabric type'], 422);
            }
        }

        if ($request->wood_color && $newWood) {
            $allowedWoodColors = WoodColor::where('wood_id', $newWood->id)
                ->pluck('id')
                ->toArray();

            if (!in_array($request->wood_color, $allowedWoodColors)) {
                return response()->json(['message' => 'Selected wood color is not available for this wood type'], 422);
            }
        }

        $itemDetail = $itemDetails->first();

        $oldWood = Wood::find($itemDetail->wood_id);
        $oldFabric = Fabric::find($itemDetail->fabric_id);

        // أبعاد الخشب الأصلية
        $originalLength = $itemDetail->wood_length;
        $originalWidth = $itemDetail->wood_width;
        $originalHeight = $itemDetail->wood_height;

        // أبعاد الخشب الجديدة
        $newLength = $request->new_length ?? $originalLength;
        $newWidth  = $request->new_width ?? $originalWidth;
        $newHeight = $request->new_height ?? $originalHeight;

        // الفرق بين الأبعاد
        $diffs = [
            $newLength - $originalLength,
            $newWidth - $originalWidth,
            $newHeight - $originalHeight,
        ];

        // تعديل السعر بحسب اختلاف الأبعاد
        $woodPricePerMeter = $newWood?->price_per_meter ?? $oldWood?->price_per_meter ?? 0;
        $dimensionAdjustment = 0;
        foreach ($diffs as $diff) {
            $dimensionAdjustment += $diff * 0.1 * $woodPricePerMeter;
        }

        // حساب مساحة الخشب الأصلية
        $woodArea = 2 * (
            $originalLength * $originalWidth +
            $originalLength * $originalHeight +
            $originalWidth * $originalHeight
        );
        $woodAreaM2 = $woodArea / 10_000;

        $oldWoodPrice = $oldWood ? $woodAreaM2 * $oldWood->price_per_meter : 0;
        $newWoodPrice = $newWood ? $woodAreaM2 * $newWood->price_per_meter : 0;

        // تعديل أبعاد القماش تلقائياً بناءً على تغييرات الطول والعرض للخشب
        $fabricLengthDiff = $newLength - $originalLength;
        $fabricWidthDiff = $newWidth - $originalWidth;

        $fabricOriginalLength = $originalLength;
        $fabricOriginalWidth = $originalWidth;

        $newFabricLength = $fabricOriginalLength + $fabricLengthDiff;
        $newFabricWidth = $fabricOriginalWidth + $fabricWidthDiff;

        $newFabricArea = $newFabricLength * $newFabricWidth;
        $newFabricAreaM2 = $newFabricArea / 10_000;

        $oldFabricPrice = $oldFabric ? $itemDetail->fabric_dimension * $oldFabric->price_per_meter : 0;
        $newFabricPrice = $newFabric ? $newFabricAreaM2 * $newFabric->price_per_meter : 0;

        $finalPrice = $item->price;
        if ($newWood) {
            $finalPrice = $finalPrice - $oldWoodPrice + $newWoodPrice;
        }
        if ($newFabric) {
            $finalPrice = $finalPrice - $oldFabricPrice + $newFabricPrice;
        }
        $finalPrice += $dimensionAdjustment;

        $originalTime = $itemDetail->time ?? 0;
        $finalTime = $originalTime + 5;

        $newWoodColor = $request->wood_color ?? null;
        $newFabricColor = $request->fabric_color ?? null;

        if ($existingCustomization) {
            $existingCustomization->update([
                'wood_id' => $newWood?->id ?? $existingCustomization->wood_id,
                'fabric_id' => $newFabric?->id ?? $existingCustomization->fabric_id,
                'new_length' => $newLength,
                'new_width' => $newWidth,
                'new_height' => $newHeight,
                'final_price' => $finalPrice,
                'wood_color' => $newWoodColor ?? $existingCustomization->wood_color,
                'fabric_color' => $newFabricColor ?? $existingCustomization->fabric_color,
            ]);

            $customization = $existingCustomization;
        } else {
            $customization = Customization::create([
                'item_id' => $item->id,
                'customer_id' => $user->id,
                'wood_id' => $newWood?->id,
                'fabric_id' => $newFabric?->id,
                'new_length' => $newLength,
                'new_width' => $newWidth,
                'new_height' => $newHeight,
                'old_price' => $item->price,
                'final_price' => $finalPrice,
                'wood_color' => $newWoodColor,
                'fabric_color' => $newFabricColor,
            ]);
        }

        return response()->json([
            'message' => 'Customization done successfully.',
            'customization' => $customization,
            'final_time' => $finalTime,
            'customization_id' => $customization->id,
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

    // public function isItemCustomized($itemId)
    // {
    //     $user = auth()->user();

    //     if (!$user || !$user->customer) {
    //         return response()->json(['message' => 'login is required'], 200);
    //     }

    //     $customerId = $user->customer->id;

    //     $isCustomized = \App\Models\Customization::where('item_id', $itemId)
    //         ->where('customer_id', $customerId)
    //         ->exists();

    //     return $isCustomized;
    // }

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

    public function getMaterialOptions($itemId)
    {
        $item = Item::with(
            'itemDetail.wood.WoodColor',
            'itemDetail.wood.WoodType',
            'itemDetail.fabric.fabricColor',
            'itemDetail.fabric.fabricType'
        )->find($itemId);

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $woodOptions = [];
        $fabricOptions = [];

        foreach ($item->itemDetail as $detail) {
            // WOOD
            if ($detail->wood) {
                $wood = $detail->wood;
                $woodOptions[] = [
                    'wood_id' => $wood->id,
                    'wood_name' => $wood->name,
                    'wood_type' => $wood->WoodType?->name,
                    'wood_color' => $wood->WoodColor?->name,
                    'price_per_meter' => $wood->price_per_meter,
                ];
            }

            // FABRIC
            if ($detail->fabric) {
                $fabric = $detail->fabric;
                $fabricOptions[] = [
                    'fabric_id' => $fabric->id,
                    'fabric_name' => $fabric->name,
                    'fabric_type' => $fabric->fabricType?->name,
                    'fabric_color' => $fabric->fabricColor?->name,
                    'price_per_meter' => $fabric->price_per_meter,
                ];
            }
        }

        return response()->json([
            'wood_options' => $woodOptions,
            'fabric_options' => $fabricOptions,
        ]);
    }


    public function getWoodTypesForItem($itemId)
    {
        $item = Item::with('itemDetail.wood.woodType')->find($itemId);

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $woodTypes = $item->itemDetail
            ->filter(fn($detail) => $detail->wood && $detail->wood->woodType)
            ->pluck('wood.woodType')
            ->unique('id') // remove duplicates
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
                    'price_per_meter' => $wood->price_per_meter,
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
                    'price_per_meter' => $fabric->price_per_meter,
                ];
            })
            ->values();

        return response()->json([
            'fabric_colors' => $colors,
        ]);
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
    $item = Item::findOrFail($id);

    return response()->json([
        'id' => $item->id,
        'name' => $item->name,
        'prefabUrl' => $item->glb_url,
        'thumbnailUrl' => $item->thumbnail_url,
        'scale' => 1.0
    ]);
}

}