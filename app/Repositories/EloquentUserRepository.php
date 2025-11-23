<?php

namespace App\Repositories;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function all(): Collection
    {
        return User::with(['tasks', 'createdTasks'])->get();
    }

    public function find(int $id): ?User
    {
        return User::with(['tasks', 'createdTasks', 'taskLogs'])->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }

    public function getWorkload(User $user): array
    {
        $usagePercentage = $user->max_workload > 0
            ? ($user->workload / $user->max_workload) * 100
            : 0;

        return [
            'user_id' => $user->id,
            'current_workload' => $user->workload,
            'max_workload' => $user->max_workload,
            'usage_percentage' => round($usagePercentage, 2)
        ];
    }
}
