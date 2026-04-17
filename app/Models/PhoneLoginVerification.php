<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PhoneLoginVerification extends Model
{
    protected $fillable = [
        'user_id',
        'country_code',
        'phone_e164',
        'verification_code_hash',
        'attempt_count',
        'max_attempts',
        'expires_at',
        'sent_at',
        'verified_at',
        'provider_message_id',
        'provider_status',
        'last_error',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
