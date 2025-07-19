<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class FabricColor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function fabrics()
    {
        return $this->hasMany(Fabric::class);
    }
}