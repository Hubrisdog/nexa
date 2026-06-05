<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Availability extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'user_id',
        'working_hours',
        'breaks',
        'holidays',
        'buffer_time',
        'timezone',
        'tenant_id',
    ];

    protected $casts = [
        'working_hours' => 'array',
        'breaks' => 'array',
        'holidays' => 'array',
        'buffer_time' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
