<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function update(User $user, Task $task): bool
    {
        if (in_array($user->role, ['admin', 'manager'])) {
            return true;
        }

        return $user->id === $task->assigned_user_id;
    }

    public function delete(User $user, Task $task): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function assign(User $user, Task $task): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function viewByUser(User $user, User $targetUser): bool
    {
        return in_array($user->role, ['admin', 'manager']) || $user->id === $targetUser->id;
    }

    public function viewByProject(User $user): bool
    {
        return true;
    }
}

