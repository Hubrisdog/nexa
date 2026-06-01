<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Deal extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'contact_id',
        'title',
        'value',
        'stage',
        'score',
        'tenant_id',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'score' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
