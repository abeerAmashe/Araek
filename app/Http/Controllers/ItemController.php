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
                    'price_per_meter' => $wood->woodType->price_per_meter ?? 0,
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


    // public function customizeItem(Request $request, Item $item)
    // {
    //     $customer = auth()->user()->customer;
    //     $customer_id = $customer->id;

    //     $request->validate([
    //         'wood_type_id'     => 'nullable|exists:wood_types,id',
    //         'fabric_type_id'   => 'nullable|exists:fabric_types,id',
    //         'new_length'       => 'nullable|numeric',
    //         'new_width'        => 'nullable|numeric',
    //         'new_height'       => 'nullable|numeric',
    //         'wood_color_id'    => 'nullable|exists:wood_colors,id',
    //         'fabric_color_id'  => 'nullable|exists:fabric_colors,id',
    //     ]);

    //     // تحميل العلاقات
    //     $item->load('itemDetail.wood.woodType', 'itemDetail.fabric.fabricType');
    //     $detail = $item->itemDetail->first();

    //     // النوع الأصلي
    //     $originalWoodType   = $detail->wood->woodType ?? null;
    //     $originalFabricType = $detail->fabric->fabricType ?? null;

    //     // النوع الجديد إذا تم إرساله
    //     $newWoodType   = $request->filled('wood_type_id') ? WoodType::findOrFail($request->wood_type_id) : $originalWoodType;
    //     $newFabricType = $request->filled('fabric_type_id') ? FabricType::findOrFail($request->fabric_type_id) : $originalFabricType;

    //     // المساحات
    //     $woodArea   = is_numeric($detail->wood_area_m2) ? (float) $detail->wood_area_m2 : 0;
    //     $fabricArea = is_numeric($detail->fabric_dimension) ? (float) $detail->fabric_dimension : 0;

    //     // الأسعار
    //     $originalWoodPrice  = is_numeric($originalWoodType?->price_per_meter) ? (float) $originalWoodType->price_per_meter : 0;
    //     $newWoodPrice       = is_numeric($newWoodType?->price_per_meter) ? (float) $newWoodType->price_per_meter : 0;

    //     $originalFabricPrice = is_numeric($originalFabricType?->price_per_meter) ? (float) $originalFabricType->price_per_meter : 0;
    //     $newFabricPrice      = is_numeric($newFabricType?->price_per_meter) ? (float) $newFabricType->price_per_meter : 0;

    //     // التكاليف
    //     $oldWoodCost   = $woodArea * $originalWoodPrice;
    //     $newWoodCost   = $woodArea * $newWoodPrice;

    //     $oldFabricCost = $fabricArea * $originalFabricPrice;
    //     $newFabricCost = $fabricArea * $newFabricPrice;

    //     // السعر الأساسي للقطعة
    //     $oldPrice = is_numeric($item->price) ? (float) $item->price : 0;

    //     // الفرق بالتكلفة حسب التعديل
    //     $woodDifference   = $newWoodCost - $oldWoodCost;
    //     $fabricDifference = $newFabricCost - $oldFabricCost;

    //     $finalPrice = $oldPrice + $woodDifference + $fabricDifference;

    //     // الوقت الجديد = وقت العنصر الأصلي + 5 أيام
    //     $finalTime = ((int) $item->time) + 5;

    //     // إنشاء التخصيص
    //     $customization = Customization::create([
    //         'item_id'         => $item->id,
    //         'wood_type_id'    => $newWoodType?->id,
    //         'fabric_type_id'  => $newFabricType?->id,
    //         'wood_color_id'   => $request->wood_color_id,
    //         'fabric_color_id' => $request->fabric_color_id,
    //         'new_length'      => $request->new_length,
    //         'new_width'       => $request->new_width,
    //         'new_height'      => $request->new_height,
    //         'old_price'       => $oldPrice,
    //         'final_price'     => $finalPrice,
    //         'final_time'      => $finalTime,
    //         'customer_id'     => $customer_id,
    //     ]);

    //     return response()->json([
    //         'message'       => 'تم تخصيص القطعة بنجاح.',
    //         'customization' => $customization,
    //     ]);
    // }


    // public function customizeItem(Request $request, Item $item)
    // {
    //     $customer_id = auth()->user()->customer->id;

    //     $request->validate([
    //         'wood_type_id'     => 'nullable|exists:wood_types,id',
    //         'fabric_type_id'   => 'nullable|exists:fabric_types,id',
    //         'new_length'       => 'nullable|numeric',
    //         'new_width'        => 'nullable|numeric',
    //         'new_height'       => 'nullable|numeric',
    //         'wood_color_id'    => 'nullable|exists:wood_colors,id',
    //         'fabric_color_id'  => 'nullable|exists:fabric_colors,id',
    //     ]);

    //     $item->load('itemDetail.wood.woodType', 'itemDetail.fabric.fabricType');
    //     $detail = $item->itemDetail->first();

    //     $originalWood = $detail->wood;
    //     $originalFabric = $detail->fabric;

    //     $originalWoodType = $originalWood?->woodType;
    //     $originalFabricType = $originalFabric?->fabricType;

    //     $newWoodType = $request->filled('wood_type_id') ? WoodType::findOrFail($request->wood_type_id) : $originalWoodType;
    //     $newFabricType = $request->filled('fabric_type_id') ? FabricType::findOrFail($request->fabric_type_id) : $originalFabricType;

    //     $woodAreaCM2 = is_numeric($detail->wood_area_m2) ? (float) $detail->wood_area_m2 : 0;

    //     [$fabricLength, $fabricWidth] = explode('*', $detail->fabric_dimension ?? '0*0');
    //     $fabricLength = floatval($fabricLength);
    //     $fabricWidth = floatval($fabricWidth);
    //     $fabricAreaM2 = ($fabricLength * $fabricWidth) / 10000;

    //     $originalWoodPrice = (float) ($originalWoodType->price_per_meter ?? 0);
    //     $newWoodPrice = (float) ($newWoodType->price_per_meter ?? 0);
    //     $originalFabricPrice = (float) ($originalFabricType->price_per_meter ?? 0);
    //     $newFabricPrice = (float) ($newFabricType->price_per_meter ?? 0);

    //     $oldWoodCost = ($woodAreaCM2 / 10000) * $originalWoodPrice;
    //     $newWoodCost = ($woodAreaCM2 / 10000) * $newWoodPrice;

    //     $oldFabricCost = $fabricAreaM2 * $originalFabricPrice;
    //     $newFabricCost = $fabricAreaM2 * $newFabricPrice;

    //     $oldPrice = (float) $item->price;
    //     $priceDifference = ($newWoodCost - $oldWoodCost) + ($newFabricCost - $oldFabricCost);

    //     // حساب التغيير في الأبعاد
    //     $originalLength = (float) $detail->wood_length;
    //     $originalWidth = (float) $detail->wood_width;
    //     $originalHeight = (float) $detail->wood_height;

    //     $newLength = $request->input('new_length', $originalLength);
    //     $newWidth = $request->input('new_width', $originalWidth);
    //     $newHeight = $request->input('new_height', $originalHeight);

    //     $dimensionAdjustment = 0;
    //     foreach (
    //         [
    //             ['old' => $originalLength, 'new' => $newLength],
    //             ['old' => $originalWidth, 'new' => $newWidth],
    //             ['old' => $originalHeight, 'new' => $newHeight],
    //         ] as $dim
    //     ) {
    //         $diffMeters = ($dim['new'] - $dim['old']) / 100;
    //         $dimensionAdjustment += $diffMeters * 0.10;
    //     }

    //     $finalPrice = $oldPrice + $priceDifference;
    //     $finalPrice *= (1 + $dimensionAdjustment);
    //     $finalPrice = max($finalPrice, $oldPrice * 0.9);

    //     // تعديل أبعاد القماش بما يتناسب مع تعديل الخشب
    //     $fabricLength += ($newLength - $originalLength);
    //     $fabricWidth += ($newWidth - $originalWidth);
    //     $newFabricDimension = round($fabricLength, 2) . '*' . round($fabricWidth, 2);

    //     $finalTime = $item->time + 5;

    //     $customization = Customization::create([
    //         'item_id'          => $item->id,
    //         'wood_type_id'     => $request->input('wood_type_id'),
    //         'fabric_type_id'   => $request->input('fabric_type_id'),
    //         'wood_color_id'    => $request->input('wood_color_id'),
    //         'fabric_color_id'  => $request->input('fabric_color_id'),
    //         'new_length'       => $newLength,
    //         'new_width'        => $newWidth,
    //         'new_height'       => $newHeight,
    //         'old_price'        => $oldPrice,
    //         'final_price'      => $finalPrice,
    //         'final_time'       => $finalTime,
    //         'fabric_dimension' => $newFabricDimension,
    //         'customer_id'      => $customer_id,
    //     ]);

    //     return response()->json([
    //         'message' => 'تم تخصيص القطعة بنجاح.',
    //         'customization' => $customization,
    //     ]);
    // }






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
        $item = Item::findOrFail($id);

        return response()->json([
            'id' => $item->id,
            'name' => $item->name,
            'prefabUrl' => $item->glb_url,
            'thumbnailUrl' => $item->thumbnail_url,
            'scale' => 1.0
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
    //     $request->validate([
    //         'wood_id' => 'nullable|exists:woods,id',
    //         'fabric_id' => 'nullable|exists:fabrics,id',
    //         'wood_type_id' => 'nullable|exists:wood_types,id',
    //         'wood_color_id' => 'nullable|exists:wood_colors,id',
    //         'fabric_type_id' => 'nullable|exists:fabric_types,id',
    //         'fabric_color_id' => 'nullable|exists:fabric_colors,id',
    //         'new_length' => 'nullable|numeric',
    //         'new_width' => 'nullable|numeric',
    //         'new_height' => 'nullable|numeric',
    //     ]);

    //     $item->load('itemDetail.wood.woodType', 'itemDetail.fabric.fabricType');
    //     $itemDetail = $item->itemDetail->first();

    //     if (!$itemDetail) {
    //         return response()->json(['message' => 'Item detail not found'], 404);
    //     }

    //     // القيم الأصلية
    //     $originalWood = $itemDetail->wood;
    //     $originalFabric = $itemDetail->fabric;
    //     $originalWoodType = $originalWood->woodType ?? null;
    //     $originalFabricType = $originalFabric->fabricType ?? null;
    //     $originalWoodArea = $itemDetail->wood_area_m2 ?? 0;
    //     $originalFabricLength = $itemDetail->fabric_length ?? 0;
    //     $originalFabricWidth = $itemDetail->fabric_width ?? 0;
    //     $originalPrice = $item->price;

    //     $finalPrice = $originalPrice;

    //     // تعديل حسب نوع الخشب
    //     if ($request->wood_type_id && $originalWoodType && $request->wood_type_id != $originalWoodType->id) {
    //         $newWoodType = \App\Models\WoodType::find($request->wood_type_id);
    //         $oldWoodCost = $originalWoodArea * ($originalWoodType->price_per_meter ?? 0);
    //         $newWoodCost = $originalWoodArea * ($newWoodType->price_per_meter ?? 0);
    //         $finalPrice = $finalPrice - $oldWoodCost + $newWoodCost;
    //     }

    //     // تعديل حسب نوع القماش
    //     if ($request->fabric_type_id && $originalFabricType && $request->fabric_type_id != $originalFabricType->id) {
    //         $fabricArea = ($originalFabricLength / 100) * ($originalFabricWidth / 100); // m²
    //         $oldFabricCost = $fabricArea * ($originalFabricType->price_per_meter ?? 0);
    //         $newFabricType = \App\Models\FabricType::find($request->fabric_type_id);
    //         $newFabricCost = $fabricArea * ($newFabricType->price_per_meter ?? 0);
    //         $finalPrice = $finalPrice - $oldFabricCost + $newFabricCost;
    //     }

    //     // الأبعاد الجديدة
    //     $originalLength = $itemDetail->wood_length ?? 0;
    //     $originalWidth = $itemDetail->wood_width ?? 0;
    //     $originalHeight = $itemDetail->wood_height ?? 0;

    //     $newLength = $request->new_length ?? $originalLength;
    //     $newWidth = $request->new_width ?? $originalWidth;
    //     $newHeight = $request->new_height ?? $originalHeight;

    //     $lengthDifference = $newLength - $originalLength;
    //     $widthDifference = $newWidth - $originalWidth;
    //     $heightDifference = $newHeight - $originalHeight;

    //     // تعديل السعر حسب فرق الطول
    //     if (abs($lengthDifference) >= 100) {
    //         $change = 0.10 * $originalPrice;
    //         if ($lengthDifference > 0) {
    //             $finalPrice += $change;
    //         } else {
    //             $minimumAllowedPrice = $originalPrice * 0.9;
    //             $finalPrice = max($finalPrice - $change, $minimumAllowedPrice);
    //         }
    //     }

    //     // تعديل أبعاد القماش
    //     $newFabricLength = $originalFabricLength;
    //     $newFabricWidth = $originalFabricWidth;

    //     if ($heightDifference > 0) {
    //         $newFabricLength += $heightDifference;
    //     }

    //     if ($widthDifference > 0) {
    //         $newFabricWidth += $widthDifference;
    //     }

    //     // وقت التخصيص
    //     $finalTime = $item->time + 5;

    //     // معرف الزبون
    //     $customerId = auth()->user()->customer->id;

    //     // تخزين التخصيص
    //     Customization::create([
    //         'item_id' => $item->id,
    //         'wood_id' => $request->wood_id ?? $originalWood->id,
    //         'wood_type_id' => $request->wood_type_id ?? $originalWoodType?->id,
    //         'wood_color_id' => $request->wood_color_id ?? $originalWood->wood_color_id,
    //         'fabric_id' => $request->fabric_id ?? $originalFabric->id,
    //         'fabric_type_id' => $request->fabric_type_id ?? $originalFabricType?->id,
    //         'fabric_color_id' => $request->fabric_color_id ?? $originalFabric->fabric_color_id,
    //         'new_length' => $newLength,
    //         'new_width' => $newWidth,
    //         'new_height' => $newHeight,
    //         'old_price' => $originalPrice,
    //         'final_price' => $finalPrice,
    //         'final_time' => $finalTime,
    //         'wood_color' => $request->wood_color_id ?? $originalWood->wood_color_id,
    //         'fabric_color' => $request->fabric_color_id ?? $originalFabric->fabric_color_id,
    //         'fabric_length' => $newFabricLength,
    //         'fabric_width' => $newFabricWidth,
    //         'customer_id' => $customerId,
    //     ]);

    //     return response()->json([
    //         'message' => 'Customization created successfully.',
    //         'orginal_price'=>$item->price,
    //         'final_price' => $finalPrice,
    //         'final_time' => $finalTime,
    //         'fabric_length' => $newFabricLength,
    //         'fabric_width' => $newFabricWidth,
    //     ]);
    // }

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

        // القيم الأصلية
        $originalWood = $itemDetail->wood;
        $originalFabric = $itemDetail->fabric;
        $originalWoodType = $originalWood->woodType ?? null;
        $originalFabricType = $originalFabric->fabricType ?? null;
        $originalWoodArea = $itemDetail->wood_area_m2 ?? 0;
        $originalFabricLength = $itemDetail->fabric_length ?? 0;
        $originalFabricWidth = $itemDetail->fabric_width ?? 0;
        $originalPrice = $item->price;

        $finalPrice = $originalPrice;

        // تعديل حسب نوع الخشب
        if ($request->wood_type_id && $originalWoodType && $request->wood_type_id != $originalWoodType->id) {
            $newWoodType = \App\Models\WoodType::find($request->wood_type_id);
            $oldWoodCost = $originalWoodArea * ($originalWoodType->price_per_meter ?? 0);
            $newWoodCost = $originalWoodArea * ($newWoodType->price_per_meter ?? 0);
            $finalPrice = $finalPrice - $oldWoodCost + $newWoodCost;
        }

        // تعديل حسب نوع القماش
        if ($request->fabric_type_id && $originalFabricType && $request->fabric_type_id != $originalFabricType->id) {
            $fabricArea = ($originalFabricLength / 100) * ($originalFabricWidth / 100); // m²
            $oldFabricCost = $fabricArea * ($originalFabricType->price_per_meter ?? 0);
            $newFabricType = \App\Models\FabricType::find($request->fabric_type_id);
            $newFabricCost = $fabricArea * ($newFabricType->price_per_meter ?? 0);
            $finalPrice = $finalPrice - $oldFabricCost + $newFabricCost;
        }

        // الأبعاد الأصلية
        $originalLength = $itemDetail->wood_length ?? 0;
        $originalWidth = $itemDetail->wood_width ?? 0;
        $originalHeight = $itemDetail->wood_height ?? 0;

        $newLength = $request->new_length ?? $originalLength;
        $newWidth = $request->new_width ?? $originalWidth;
        $newHeight = $request->new_height ?? $originalHeight;

        $lengthDifference = $newLength - $originalLength;
        $widthDifference = $newWidth - $originalWidth;
        $heightDifference = $newHeight - $originalHeight;

        // تعديل السعر حسب فرق الطول (نسبي حتى لو أقل من متر)
        if ($lengthDifference != 0) {
            $percentageChange = ($lengthDifference / 100) * 0.10;
            $change = $percentageChange * $originalPrice;

            if ($lengthDifference > 0) {
                $finalPrice += $change;
            } else {
                $minimumAllowedPrice = $originalPrice * 0.9;
                $finalPrice = max($finalPrice + $change, $minimumAllowedPrice);
            }
        }

        // تعديل السعر حسب فرق العرض (نسبي فقط إذا زاد)
        if ($widthDifference > 0) {
            $percentageChange = ($widthDifference / 100) * 0.10;
            $change = $percentageChange * $originalPrice;
            $finalPrice += $change;
        }

        // تعديل السعر حسب فرق الارتفاع (نسبي فقط إذا زاد)
        if ($heightDifference > 0) {
            $percentageChange = ($heightDifference / 100) * 0.10;
            $change = $percentageChange * $originalPrice;
            $finalPrice += $change;
        }

        // تعديل أبعاد القماش
        $newFabricLength = $originalFabricLength;
        $newFabricWidth = $originalFabricWidth;

        if ($heightDifference > 0) {
            $newFabricLength += $heightDifference;
        }

        if ($widthDifference > 0) {
            $newFabricWidth += $widthDifference;
        }

        // وقت التخصيص
        $finalTime = $item->time + 5;

        // معرف الزبون
        $customerId = auth()->user()->customer->id;

        // تخزين التخصيص
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
                    'price_per_meter' => $wood->woodType->price_per_meter ?? 0,

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
                    'price_per_meter' => $fabric->fabricType->price_per_meter ?? 0,
                ];
            }
        }

        return response()->json([
            'wood_options' => $woodOptions,
            'fabric_options' => $fabricOptions,
        ]);
    }














    //   public function customizeItem(Request $request, Item $item)
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
    // } rtttttttttt   

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
}
