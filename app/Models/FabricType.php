<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class FabricType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price_per_meter',

    ];

    public function fabrics()
    {
        return $this->hasMany(Fabric::class);
    }
}
