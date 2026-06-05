<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Activity extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'contact_id',
        'user_id',
        'type',
        'description',
        'tenant_id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
