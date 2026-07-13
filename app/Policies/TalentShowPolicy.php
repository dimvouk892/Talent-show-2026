<?php

namespace App\Policies;

use App\Models\TalentShow;
use App\Models\User;

class TalentShowPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, TalentShow $talentShow): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, TalentShow $talentShow): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, TalentShow $talentShow): bool
    {
        return $user->isAdmin();
    }

    public function control(User $user, TalentShow $talentShow): bool
    {
        return $user->isAdmin();
    }
}
