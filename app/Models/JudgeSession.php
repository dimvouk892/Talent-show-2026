<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JudgeSession extends Model
{
    protected $fillable = [
        'judge_id',
        'session_token_hash',
        'ip_hash',
        'user_agent_hash',
        'last_activity_at',
        'expires_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }

    public function isValid(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }

    public function touchActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }
}
