<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description'
    ];

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
