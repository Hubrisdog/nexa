<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Appointment extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'client_id',
        'staff_id',
        'title',
        'start_time',
        'end_time',
        'status',
        'note',
        'google_event_id',
        'outlook_event_id',
        'meeting_link',
        'calendar_provider',
        'tenant_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
