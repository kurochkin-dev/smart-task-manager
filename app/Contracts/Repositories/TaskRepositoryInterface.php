<?php

namespace App\Contracts\Repositories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

interface TaskRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?Task;

    public function create(array $data): Task;

    public function update(Task $task, array $data): Task;

    public function delete(Task $task): bool;

    public function findByUser(int $userId): Collection;

    public function findByProject(int $projectId): Collection;

    public function assignToUser(Task $task, int $userId): Task;
}
