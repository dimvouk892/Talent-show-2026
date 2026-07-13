<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vote extends Model
{
    protected $fillable = [
        'talent_show_id',
        'team_id',
        'judge_id',
        'score',
        'submitted_at',
        'is_admin_edited',
        'edited_by',
        'edited_at',
        'edit_reason',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'submitted_at' => 'datetime',
            'is_admin_edited' => 'boolean',
            'edited_at' => 'datetime',
        ];
    }

    public function talentShow(): BelongsTo
    {
        return $this->belongsTo(TalentShow::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(VoteRevision::class);
    }
}
