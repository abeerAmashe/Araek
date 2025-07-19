<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wood extends Model
{
    use HasFactory;

    protected $table = 'woods';

    protected $fillable = [
        'name',
        'wood_color_id',
        'wood_type_id'
    ];

    protected $casts = [
        'price_per_meter' => 'float',
    ];


    public function itemDetails()
    {
        return $this->hasMany(ItemDetail::class);
    }

    public function WoodColor()
    {
        return $this->belongsTo(WoodColor::class);
    }

    public function WoodType()
    {
        return $this->belongsTo(WoodType::class);
    }

    public function roomDetails()
    {
        return $this->hasMany(RoomDetail::class);
    }
}