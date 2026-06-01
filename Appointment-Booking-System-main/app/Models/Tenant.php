<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'plan',
        'stripe_customer_id',
        'stripe_subscription_id',
        'subscription_status',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
