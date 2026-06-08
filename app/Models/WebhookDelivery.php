<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_subscription_id',
        'event',
        'payload',
        'response_status',
        'response_body',
        'duration_ms',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function subscription()
    {
        return $this->belongsTo(WebhookSubscription::class, 'webhook_subscription_id');
    }
}
