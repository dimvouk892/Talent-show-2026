<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'judge_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }
}
