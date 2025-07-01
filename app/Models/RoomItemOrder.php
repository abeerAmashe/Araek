<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomItemOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_order_id',
        'item_id',
        'room_id',
        'count_reserved',
        'purchase_order_id'
    ];

    public function roomOrder()
    {
        return $this->belongsTo(RoomOrder::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}