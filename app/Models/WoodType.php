<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class WoodType extends Model
{

    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function wood()
    {
        return $this->hasMany(Wood::class);
    }
}