<?php

namespace App\Services;

use App\Models\TalentShow;
use Illuminate\Support\Facades\DB;

class TalentShowManagementService
{
    public function __construct(
        protected JudgeAccessService $judgeAccessService,
        protected AuditLogService $auditLogService,
    ) {}

    public function delete(TalentShow $talentShow): void
    {
        DB::transaction(function () use ($talentShow) {
            $this->judgeAccessService->revokeAllSessionsForTalentShow($talentShow);
            $this->auditLogService->clearForTalentShow($talentShow);

            $talentShow->update([
                'current_team_id' => null,
                'winner_team_id' => null,
            ]);

            $talentShow->delete();
        });
    }
}
