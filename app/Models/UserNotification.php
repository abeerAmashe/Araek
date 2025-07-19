<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use HasFactory;
     protected $fillable = [
        'notification_id',
        'user_id',
    ];
    
    public function notifcation()
    {
        return $this->belongsTo(Notification::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}