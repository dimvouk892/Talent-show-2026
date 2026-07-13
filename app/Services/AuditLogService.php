<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\TalentShow;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AuditLogService
{
    public function queryForTalentShow(TalentShow $talentShow): Builder
    {
        return AuditLog::query()->where(function (Builder $query) use ($talentShow) {
            $query->where(function (Builder $query) use ($talentShow) {
                $query->where('entity_type', 'talent_show')
                    ->where('entity_id', $talentShow->id);
            })
                ->orWhere(function (Builder $query) use ($talentShow) {
                    $query->where('entity_type', 'judge')
                        ->whereIn('entity_id', $talentShow->judges()->select('id'));
                })
                ->orWhere(function (Builder $query) use ($talentShow) {
                    $query->where('entity_type', 'vote')
                        ->whereIn('entity_id', Vote::query()
                            ->where('talent_show_id', $talentShow->id)
                            ->select('id'));
                });
        });
    }

    public function clearForTalentShow(TalentShow $talentShow): int
    {
        return $this->queryForTalentShow($talentShow)->delete();
    }

    public function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null,
        ?int $judgeId = null,
        ?Request $request = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId ?? auth()->id(),
            'judge_id' => $judgeId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_hash' => $request ? $this->hashIp($request->ip()) : null,
        ]);
    }

    public function hashIp(?string $ip): ?string
    {
        return $ip ? hash('sha256', $ip) : null;
    }

    public function hashUserAgent(?string $userAgent): ?string
    {
        return $userAgent ? hash('sha256', $userAgent) : null;
    }
}
