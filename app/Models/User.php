<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'telegram_id',
        'subscription',
    ];

    protected $casts = [
        'subscription' => 'boolean',
    ];

    public function scopeSubscribed($query)
    {
        return $query->where('subscription', true);
    }
}
