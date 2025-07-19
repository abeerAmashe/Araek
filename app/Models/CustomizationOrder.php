<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomizationOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'customization_id',
        'count',
        'deposite_price',
        'deposite_time',
        'delivery_time',
        'status'
    ];

    public function customization()
    {
        return $this->belongsTo(customization::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}