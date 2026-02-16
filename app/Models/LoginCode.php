<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code_hash',
        'expires_at',
        'consumed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    /**
     * Active code scope excludes expired or consumed codes.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('consumed_at')->where('expires_at', '>', now());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
