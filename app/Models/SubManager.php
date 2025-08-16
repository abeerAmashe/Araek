<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubManager extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'id',
        'photo',
        'phone',


    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->hasOne(Branch::class);
    }
}
