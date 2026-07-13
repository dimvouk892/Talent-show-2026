<?php

namespace App\Models;

use App\Enums\TalentShowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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
        'showing_team_intro',
        'opening_video_path',
        'closing_video_path',
        'waiting_video_path',
        'waiting_image_path',
        'showing_opening_video',
        'showing_closing_video',
        'showing_waiting_video',
        'showing_waiting_image',
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
            'showing_team_intro' => 'boolean',
            'showing_opening_video' => 'boolean',
            'showing_closing_video' => 'boolean',
            'showing_waiting_video' => 'boolean',
            'showing_waiting_image' => 'boolean',
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

    public function allowsVoting(): bool
    {
        return $this->status->allowsVoting();
    }

    public function openingVideoUrl(): ?string
    {
        if (! $this->opening_video_path) {
            return null;
        }

        return Storage::disk('public')->url($this->opening_video_path);
    }

    public function closingVideoUrl(): ?string
    {
        if (! $this->closing_video_path) {
            return null;
        }

        return Storage::disk('public')->url($this->closing_video_path);
    }

    public function hasOpeningVideo(): bool
    {
        return (bool) $this->opening_video_path;
    }

    public function hasClosingVideo(): bool
    {
        return (bool) $this->closing_video_path;
    }

    public function waitingVideoUrl(): ?string
    {
        if (! $this->waiting_video_path) {
            return null;
        }

        return Storage::disk('public')->url($this->waiting_video_path);
    }

    public function hasWaitingVideo(): bool
    {
        return (bool) $this->waiting_video_path;
    }

    public function waitingImageUrl(): ?string
    {
        if (! $this->waiting_image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->waiting_image_path);
    }

    public function hasWaitingImage(): bool
    {
        return (bool) $this->waiting_image_path;
    }

    public function isOnWaitingScreen(): bool
    {
        return $this->current_team_id === null
            && ! $this->showing_opening_video
            && ! $this->showing_closing_video
            && ! $this->show_ranking
            && ! $this->winner_revealed
            && $this->status !== TalentShowStatus::Archived;
    }

    public function shouldDisplayWaitingVideo(): bool
    {
        return $this->hasWaitingVideo()
            && $this->showing_waiting_video
            && $this->isOnWaitingScreen();
    }

    public function shouldDisplayWaitingImage(): bool
    {
        return $this->hasWaitingImage()
            && $this->showing_waiting_image
            && $this->isOnWaitingScreen();
    }
}
