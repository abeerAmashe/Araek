<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomizationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_customization_id',
        'item_id',
        'new_length',
        'new_width',
        'new_height',
        'fabric_length',
        'fabric_width'
     
    ];

    public function roomCustomization()
    {
        return $this->belongsTo(RoomCustomization::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}