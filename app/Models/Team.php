<?php

namespace App\Models;

use App\Enums\TeamStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = [
        'talent_show_id',
        'name',
        'code',
        'description',
        'display_order',
        'status',
        'is_active',
        'scoring_completed_at',
        'score_revealed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TeamStatus::class,
            'is_active' => 'boolean',
            'display_order' => 'integer',
            'scoring_completed_at' => 'datetime',
            'score_revealed_at' => 'datetime',
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

    public function scopeActive($query)
    {
        return $query->where('status', TeamStatus::Active);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('id');
    }

    public function hasVotes(): bool
    {
        return $this->votes()->exists();
    }
}
