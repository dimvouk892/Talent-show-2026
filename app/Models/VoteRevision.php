<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoteRevision extends Model
{
    protected $fillable = [
        'vote_id',
        'old_score',
        'new_score',
        'changed_by',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'old_score' => 'integer',
            'new_score' => 'integer',
        ];
    }

    public function vote(): BelongsTo
    {
        return $this->belongsTo(Vote::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
