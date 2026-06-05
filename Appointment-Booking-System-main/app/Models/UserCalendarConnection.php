<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class UserCalendarConnection extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'user_id',
        'provider',
        'email',
        'access_token',
        'refresh_token',
        'expires_at',
        'calendar_id',
        'tenant_id',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
