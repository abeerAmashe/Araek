<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomCustomization extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'customer_id',
        'wood_type_id',
        'wood_color_id',
        'fabric_type_id',
        'fabric_color_id',
        'deposite_price',
        'deposite_time',
        'final_time',
        'final_price'
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function woodType()
    {
        return $this->belongsTo(WoodType::class, 'wood_type_id');
    }

    public function woodColor()
    {
        return $this->belongsTo(WoodColor::class, 'wood_color_id');
    }

    public function fabricType()
    {
        return $this->belongsTo(FabricType::class, 'fabric_type_id');
    }

    public function fabricColor()
    {
        return $this->belongsTo(FabricColor::class, 'fabric_color_id');
    }

    public function customizationItems()
    {
        return $this->hasMany(CustomizationItem::class);
    }
}