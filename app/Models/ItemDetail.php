<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class ItemDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'wood_id',
        'fabric_id',
        'wood_length',
        'wood_width',
        'wood_height',
        'fabric_length',
        'fabric_width',
        'fabric_dimension',
        'wood_area_m2'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function wood()
    {
        return $this->belongsTo(Wood::class);
    }

    public function fabric()
    {
        return $this->belongsTo(Fabric::class);
    }
}
