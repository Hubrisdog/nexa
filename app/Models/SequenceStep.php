<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SequenceStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'sequence_id',
        'delay_hours',
        'type',
        'subject',
        'body',
    ];

    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }
}
