<?php

namespace App\Services;

use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

readonly class ProjectService
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private CacheService               $cacheService
    ) {}

    public function getAllProjects(): Collection
    {
        return $this->cacheService->rememberProjects(
            fn() => $this->projectRepository->all()
        );
    }

    public function getProject(int $id): ?Project
    {
        return $this->cacheService->rememberProject(
            $id,
            fn() => $this->projectRepository->find($id)
        );
    }

    public function createProject(array $data): Project
    {
        $project = $this->projectRepository->create($data);
        $this->cacheService->invalidateProject($project);

        return $project;
    }

    public function updateProject(Project $project, array $data): Project
    {
        $project = $this->projectRepository->update($project, $data);
        $this->cacheService->invalidateProject($project);

        return $project;
    }

    public function deleteProject(Project $project): bool
    {
        $projectId = $project->id;
        $result = $this->projectRepository->delete($project);

        if ($result) {
            $this->cacheService->invalidateProjectWithTasks($projectId);
        }

        return $result;
    }
}
