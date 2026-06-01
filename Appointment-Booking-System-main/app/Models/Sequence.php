<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Sequence extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'name',
        'trigger_type',
        'trigger_value',
        'tenant_id',
    ];

    public function steps()
    {
        return $this->hasMany(SequenceStep::class);
    }

    public function logs()
    {
        return $this->hasMany(SequenceLog::class);
    }
}
