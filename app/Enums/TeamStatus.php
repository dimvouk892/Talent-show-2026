<?php

namespace App\Enums;

enum TeamStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case WaitingForJudges = 'waiting_for_judges';
    case ScoringCompleted = 'scoring_completed';
    case Presented = 'presented';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Εκκρεμεί',
            self::Active => 'Ενεργή',
            self::WaitingForJudges => 'Αναμονή κριτών',
            self::ScoringCompleted => 'Ολοκληρώθηκε',
            self::Presented => 'Παρουσιάστηκε',
        };
    }
}
