<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customization extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'item_id',
        'wood_id',
        'wood_type_id',
        'wood_color_id',
        'fabric_id',
        'fabric_type_id',
        'fabric_color_id',
        'new_length',
        'new_width',
        'new_height',
        'old_price',
        'final_price',
        'final_time',
        'wood_color',
        'fabric_color',
        'fabric_width',
        'fabric_length',
        'customer_id',
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