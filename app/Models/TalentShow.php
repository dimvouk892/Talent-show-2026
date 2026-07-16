<?php

namespace App\Models;

use App\Enums\TalentShowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TalentShow extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'venue',
        'event_date',
        'status',
        'current_team_id',
        'winner_team_id',
        'show_live_scores',
        'final_vote_open',
        'final_vote_submitted_at',
        'show_ranking',
        'winner_revealed',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => TalentShowStatus::class,
            'event_date' => 'date',
            'show_live_scores' => 'boolean',
            'final_vote_open' => 'boolean',
            'final_vote_submitted_at' => 'datetime',
            'show_ranking' => 'boolean',
            'winner_revealed' => 'boolean',
        ];
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function activeTeams(): HasMany
    {
        return $this->teams()->where('is_active', true);
    }

    public function judges(): HasMany
    {
        return $this->hasMany(Judge::class);
    }

    public function activeJudges(): HasMany
    {
        return $this->judges()->where('is_active', true)->ordered();
    }

    public function scoringJudges(): HasMany
    {
        return $this->activeJudges()->where('is_final_voter', false);
    }

    public function finalVoter(): ?Judge
    {
        return $this->activeJudges()->where('is_final_voter', true)->first();
    }

    public function hasPendingFinalVote(): bool
    {
        $finalVoter = $this->finalVoter();

        if (! $finalVoter) {
            return false;
        }

        return ! $finalVoter->votes()->where('talent_show_id', $this->id)->exists();
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    public function winnerTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'winner_team_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeNotArchived($query)
    {
        return $query->where('status', '!=', TalentShowStatus::Archived);
    }

    public static function forMonitor(): self
    {
        $show = static::query()
            ->notArchived()
            ->orderByDesc('id')
            ->first();

        if (! $show) {
            abort(404, 'Δεν υπάρχει ενεργό Talent Show για την οθόνη monitor.');
        }

        return $show;
    }

    public function allowsVoting(): bool
    {
        return $this->status->allowsVoting();
    }
}
