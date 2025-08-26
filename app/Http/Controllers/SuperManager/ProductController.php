<?php

namespace App\Http\Controllers\SuperManager;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Fabric;
use App\Models\FabricColor;
use App\Models\FabricType;
use App\Models\Item;
use App\Models\ItemDetail;
use App\Models\ItemType;
use App\Models\Room;
use App\Models\RoomDetail;
use App\Models\Wood;
use App\Models\WoodColor;
use App\Models\WoodType;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function storeRoom(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'image_url' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'wood_type_id' => 'required|exists:wood_types,id',
            'wood_color_id' => 'required|exists:wood_colors,id',
            'fabric_type_id' => 'required|exists:fabric_types,id',
            'fabric_color_id' => 'required|exists:fabric_colors,id',
        ]);

        $imagePath = null;
        if ($request->hasFile('image_url')) {
            $imagePath = $request->file('image_url')->store('rooms', 'public');
        }

        $wood = \App\Models\Wood::firstOrCreate(
            [
                'wood_type_id' => $validated['wood_type_id'],
                'wood_color_id' => $validated['wood_color_id'],
            ],
            [
                'name' => 'Wood ' . $validated['wood_type_id'] . '-' . $validated['wood_color_id']
            ]
        );

        $fabric = \App\Models\Fabric::firstOrCreate(
            [
                'fabric_type_id' => $validated['fabric_type_id'],
                'fabric_color_id' => $validated['fabric_color_id'],
            ],
            [
                'name' => 'Fabric ' . $validated['fabric_type_id'] . '-' . $validated['fabric_color_id']
            ]
        );

        $room = Room::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'category_id' => $validated['category_id'],
            'image_url' => $imagePath,
            'wood_type_id' => $validated['wood_type_id'],
            'wood_color_id' => $validated['wood_color_id'],
            'fabric_type_id' => $validated['fabric_type_id'],
            'fabric_color_id' => $validated['fabric_color_id'],
            'price' => 0,
            'time' => 0,
            'count' => 0,
            'count_reserved' => 0,
        ]);

        RoomDetail::create([
            'room_id' => $room->id,
            'wood_id' => $wood->id,
            'fabric_id' => $fabric->id,
        ]);

        return response()->json([
            'message' => 'Room created successfully',
            'room' => $room->load('roomDetail')
        ], 201);
    }

    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'room_id' => 'required|exists:rooms,id',
            'item_type_id' => 'required|exists:item_types,id',
            'image_url' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'glb_url' => 'nullable|file|mimes:glb,bin|max:10240', 
            'thumbnail_url' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'wood_length' => 'nullable|numeric',
            'wood_width' => 'nullable|numeric',
            'wood_height' => 'nullable|numeric',
            'fabric_length' => 'nullable|numeric',
            'fabric_width' => 'nullable|numeric',
        ]);

        $room = Room::findOrFail($validated['room_id']);

        $wood = Wood::firstOrCreate(
            [
                'wood_type_id' => $room->wood_type_id,
                'wood_color_id' => $room->wood_color_id
            ],
            [
                'name' => 'room wood' . $room->id
            ]
        );

        $fabric = Fabric::firstOrCreate(
            [
                'fabric_type_id' => $room->fabric_type_id,
                'fabric_color_id' => $room->fabric_color_id
            ],
            [
                'name' => 'room fabric' . $room->id
            ]
        );

        $imageUrl = $request->hasFile('image_url')
            ? '/storage/' . $request->file('image_url')->store('images', 'public')
            : null;

        $thumbnailUrl = $request->hasFile('thumbnail_url')
            ? '/storage/' . $request->file('thumbnail_url')->store('thumbnails', 'public')
            : null;

        $glbUrl = $request->hasFile('glb_url')
            ? '/storage/' . $request->file('glb_url')->store('glb_files', 'public')
            : null;

        $item = Item::create([
            'room_id' => $room->id,
            'item_type_id' => $validated['item_type_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image_url' => $imageUrl,
            'glb_url' => $glbUrl,
            'thumbnail_url' => $thumbnailUrl,
            'price' => 0,
            'time' => 0,
            'count' => 0,
            'count_reserved' => 0,
        ]);

        ItemDetail::create([
            'item_id' => $item->id,
            'wood_id' => $wood->id,
            'fabric_id' => $fabric->id,
            'wood_length' => $validated['wood_length'] ?? null,
            'wood_width' => $validated['wood_width'] ?? null,
            'wood_height' => $validated['wood_height'] ?? null,
            'fabric_length' => $validated['fabric_length'] ?? null,
            'fabric_width' => $validated['fabric_width'] ?? null,
        ]);

        return response()->json([
            'message' => 'Item created successfully',
        ], 201);
    }

    public function storeOptions(Request $request, $roomId)
    {
        $validated = $request->validate([
            'options' => 'required|array',
            'options.*.wood_id' => 'nullable|exists:woods,id',
            'options.*.fabric_id' => 'nullable|exists:fabrics,id',
        ]);

        $room = Room::findOrFail($roomId);

        RoomDetail::where('room_id', $room->id)->delete();

        foreach ($validated['options'] as $option) {
            RoomDetail::create([
                'room_id'   => $room->id,
                'wood_id'   => $option['wood_id'] ?? null,
                'fabric_id' => $option['fabric_id'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Done ^_^',
            'room' => $room->load('roomDetails.wood', 'roomDetails.fabric')
        ]);
    }


    public function storeWood(Request $request)
    {
        $validated = $request->validate([
            'wood_type_name'  => 'required|string|max:255',
            'price_per_meter' => 'nullable|numeric|min:0',
            'wood_color_name' => 'required|string|max:255',
        ]);

        $woodType = WoodType::where('name', $validated['wood_type_name'])->first();

        if (!$woodType) {
            $woodType = WoodType::create([
                'name'            => $validated['wood_type_name'],
                'price_per_meter' => $validated['price_per_meter'] ?? 0,
            ]);
        }

        $woodColor = WoodColor::where('name', $validated['wood_color_name'])->first();

        if (!$woodColor) {
            $woodColor = WoodColor::create([
                'name' => $validated['wood_color_name'],
            ]);
        }

        $existingWood = Wood::where('wood_type_id', $woodType->id)
            ->where('wood_color_id', $woodColor->id)
            ->first();

        if ($existingWood) {
            return response()->json([
                'message' => 'already exist',
                'data' => $existingWood->load('woodType', 'woodColor')
            ], 200);
        }

        $wood = Wood::create([
            'wood_type_id'  => $woodType->id,
            'wood_color_id' => $woodColor->id,
            'name'          => "{$woodType->name} - {$woodColor->name}",
        ]);

        return response()->json([
            'message' => 'Done ^_^',
            'data' => $wood->load('woodType', 'woodColor')
        ], 201);
    }

    public function storeFabric(Request $request)
    {
        $validated = $request->validate([
            'fabric_type_name'  => 'required|string|max:255',
            'price_per_meter'   => 'nullable|numeric|min:0',
            'fabric_color_name' => 'required|string|max:255',
        ]);

        $fabricType = FabricType::where('name', $validated['fabric_type_name'])->first();

        if (!$fabricType) {
            $fabricType = FabricType::create([
                'name'            => $validated['fabric_type_name'],
                'price_per_meter' => $validated['price_per_meter'] ?? 0,
            ]);
        }

        $fabricColor = FabricColor::where('name', $validated['fabric_color_name'])->first();

        if (!$fabricColor) {
            $fabricColor = FabricColor::create([
                'name' => $validated['fabric_color_name'],
            ]);
        }

        $existingFabric = Fabric::where('fabric_type_id', $fabricType->id)
            ->where('fabric_color_id', $fabricColor->id)
            ->first();

        if ($existingFabric) {
            return response()->json([
                'message' => 'already exist',
                'data' => $existingFabric->load('fabricType', 'fabricColor')
            ], 200);
        }

        $fabric = Fabric::create([
            'fabric_type_id'  => $fabricType->id,
            'fabric_color_id' => $fabricColor->id,
            'name'            => "{$fabricType->name} - {$fabricColor->name}",
        ]);

        return response()->json([
            'message' => 'Done ^_^',
            'data' => $fabric->load('fabricType', 'fabricColor')
        ], 201);
    }
    public function getAllRooms()
    {
        $rooms = Room::select('id', 'name', 'image_url', 'description')->get();

        return response()->json([
            'rooms' => $rooms
        ], 200);
    }

    public function getAllItems()
    {
        $items = Item::select('id', 'name', 'description', 'price')->get();

        return response()->json([
            'items' => $items
        ], 200);
    }

    public function updateItem(Request $request, $itemId)
    {
        $validated = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'description'  => 'sometimes|string',
            'price'        => 'sometimes|numeric|min:0',
            'item_type_id' => 'sometimes|exists:item_types,id',
            'image'        => 'sometimes|file|image|mimes:jpeg,png,jpg,gif|max:2048',

        ]);

        $item = Item::findOrFail($itemId);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('items', 'public');

            $validated['image_url'] = 'storage/' . $path;
        }
        $item->update($validated);

        return response()->json([
            'message' => 'Done ^_^',
            'item'    => $item
        ], 200);
    }

    public function deleteItem($itemId)
    {
        $item = Item::findOrFail($itemId);

        $item->delete();

        return response()->json([
            'message' => 'Done ^_^ '
        ], 200);
    }

    public function updateRoom(Request $request, $roomId)
    {
        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price'       => 'sometimes|numeric|min:0',
            'image_url'   => 'sometimes|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'category_id' => 'sometimes|exists:categories,id',
        ]);

        $room = Room::findOrFail($roomId);

        $room->update($validated);

        return response()->json([
            'message' => 'Done ^_-',
            'room'    => $room
        ], 200);
    }

    public function updateFabricPrice(Request $request, $fabricTypeId)
    {
        $validated = $request->validate([
            'price_per_meter' => 'required|numeric|min:0',
        ]);

        $fabricType = FabricType::findOrFail($fabricTypeId);

        $fabricType->update([
            'price_per_meter' => $validated['price_per_meter'],
        ]);

        return response()->json([
            'message' => 'Done',
            'fabric_type' => $fabricType
        ], 200);
    }

    public function updateWoodPrice(Request $request, $woodTypeId)
    {
        $validated = $request->validate([
            'price_per_meter' => 'required|numeric|min:0',
        ]);

        $woodType = WoodType::findOrFail($woodTypeId);

        $woodType->update([
            'price_per_meter' => $validated['price_per_meter'],
        ]);

        return response()->json([
            'message' => 'Done',
            'wood_type' => $woodType
        ], 200);
    }

    public function storeItemType(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:item_types,name',
            'description' => 'nullable|string',
        ]);

        $itemType = ItemType::create($validated);

        return response()->json([
            'message'   => 'Done ^_-',
            'item_type' => $itemType
        ], 201);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        $category = Category::create($validated);

        return response()->json([
            'message'  => 'Done ^_-',
            'category' => $category
        ], 201);
    }
}