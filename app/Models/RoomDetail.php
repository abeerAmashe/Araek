<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'wood_id',
        'fabric_id',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
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