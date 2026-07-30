<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\User;

class BoardPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Board $board): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Board $board): bool
    {
        return $user->isAdmin();
    }
}
