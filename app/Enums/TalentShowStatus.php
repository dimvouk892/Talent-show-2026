<?php

namespace App\Enums;

enum TalentShowStatus: string
{
    case Draft = 'draft';
    case Ready = 'ready';
    case ScoringOpen = 'scoring_open';
    case ScoringClosed = 'scoring_closed';
    case ResultsReady = 'results_ready';
    case WinnerRevealed = 'winner_revealed';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Πρόχειρο',
            self::Ready => 'Έτοιμο',
            self::ScoringOpen => 'Ανοιχτή βαθμολόγηση',
            self::ScoringClosed => 'Κλειστή βαθμολόγηση',
            self::ResultsReady => 'Αποτελέσματα έτοιμα',
            self::WinnerRevealed => 'Αποκαλύφθηκε νικητής',
            self::Completed => 'Ολοκληρωμένο',
            self::Archived => 'Αρχειοθετημένο',
        };
    }

    public function allowsVoting(): bool
    {
        return $this === self::ScoringOpen;
    }

    public function allowsJudgeSession(): bool
    {
        return ! in_array($this, [self::Completed, self::Archived], true);
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::Completed, self::Archived], true);
    }
}
