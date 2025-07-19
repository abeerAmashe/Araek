<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItemReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'item_id',
        'count_reserved',
        'room_id'
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}