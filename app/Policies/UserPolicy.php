<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function view(User $user, User $model): bool
    {
        return $user->role === 'admin' || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, User $model): bool
    {
        return $user->role === 'admin' || $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->role === 'admin' && $user->id !== $model->id;
    }

    public function viewWorkload(User $user, User $model): bool
    {
        return in_array($user->role, ['admin', 'manager']) || $user->id === $model->id;
    }
}

