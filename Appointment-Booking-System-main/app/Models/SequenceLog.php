<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class SequenceLog extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'sequence_id',
        'step_id',
        'appointment_id',
        'deal_id',
        'recipient',
        'status',
        'scheduled_for',
        'sent_at',
        'tenant_id',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }

    public function step()
    {
        return $this->belongsTo(SequenceStep::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }
}
