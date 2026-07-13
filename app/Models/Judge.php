<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Judge extends Model
{
    protected $fillable = [
        'talent_show_id',
        'name',
        'title',
        'display_order',
        'access_token_hash',
        'access_token_encrypted',
        'token_generated_at',
        'token_expires_at',
        'is_active',
        'last_access_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'display_order' => 'integer',
            'access_token_encrypted' => 'encrypted',
            'token_generated_at' => 'datetime',
            'token_expires_at' => 'datetime',
            'last_access_at' => 'datetime',
        ];
    }

    public function talentShow(): BelongsTo
    {
        return $this->belongsTo(TalentShow::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(JudgeSession::class);
    }

    public function activeSessions(): HasMany
    {
        return $this->sessions()
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }

    public function hasVotes(): bool
    {
        return $this->votes()->exists();
    }

    public function hasValidToken(): bool
    {
        return $this->access_token_hash
            && $this->token_expires_at
            && $this->token_expires_at->isFuture();
    }

    public function plainAccessToken(): ?string
    {
        if (! $this->hasValidToken()) {
            return null;
        }

        return $this->access_token_encrypted;
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('id');
    }
}
