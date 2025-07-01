<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'item_type_id',
        'name',
        'image_url',
        'price',
        'description',
        'count',
    ];

    protected $casts = [
        'price' => 'float',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function itemType()
    {
        return $this->belongsTo(ItemType::class);
    }

    public function itemDetail()
    {
        return $this->hasMany(ItemDetail::class);
    }

}