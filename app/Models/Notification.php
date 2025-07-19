<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
     protected $fillable = [
        'title',
        'description',
        'is_general'
    ];

    public function user_notifications(){
        return $this->hasMany(userNotification::class);
    }
}