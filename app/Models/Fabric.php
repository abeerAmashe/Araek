<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fabric extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'fabric_color_id',
        'fabric_type_id'
    ];

    protected $casts = [
        'price_per_meter' => 'float',
    ];



    public function itemDetails()
    {
        return $this->hasMany(ItemDetail::class);
    }

    public function fabricColor()
    {
        return $this->belongsTo(FabricColor::class);
    }

    public function fabricType()
    {
        return $this->belongsTo(FabricType::class);
    }

    // App\Models\Fabric.php
    public function roomDetails()
    {
        return $this->hasMany(RoomDetail::class);
    }
}