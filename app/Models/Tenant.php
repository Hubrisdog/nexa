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
        'logo_path',
        'brand_color',
        'custom_domain',
        'custom_email_footer',
        'booking_page_theme',
        'is_demo',
    ];

    protected $casts = [
        'is_demo' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
