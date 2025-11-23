<?php

namespace Tests\Unit\Services;

use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use App\Services\CacheService;
use App\Services\ProjectService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class ProjectServiceTest extends TestCase
{
    private ProjectRepositoryInterface $projectRepository;
    private CacheService $cacheService;
    private ProjectService $projectService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRepository = Mockery::mock(ProjectRepositoryInterface::class);
        $this->cacheService = Mockery::mock(CacheService::class);
        $this->projectService = new ProjectService($this->projectRepository, $this->cacheService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_all_projects_calls_repository_and_cache(): void
    {
        $projects = new Collection([new Project()]);

        $this->cacheService
            ->shouldReceive('rememberProjects')
            ->once()
            ->andReturn($projects);

        $result = $this->projectService->getAllProjects();

        $this->assertEquals($projects, $result);
    }

    public function test_get_project_calls_repository_and_cache(): void
    {
        $project = new Project(['id' => 1]);

        $this->cacheService
            ->shouldReceive('rememberProject')
            ->with(1, Mockery::type('callable'))
            ->once()
            ->andReturn($project);

        $result = $this->projectService->getProject(1);

        $this->assertEquals($project, $result);
    }

    public function test_create_project_calls_repository_and_invalidates_cache(): void
    {
        $data = ['name' => 'Test Project'];
        $project = new Project($data);

        $this->projectRepository
            ->shouldReceive('create')
            ->with($data)
            ->once()
            ->andReturn($project);

        $this->cacheService
            ->shouldReceive('invalidateProject')
            ->with($project)
            ->once();

        $result = $this->projectService->createProject($data);

        $this->assertEquals($project, $result);
    }

    public function test_update_project_calls_repository_and_invalidates_cache(): void
    {
        $project = new Project(['id' => 1, 'name' => 'Old Name']);
        $updatedProject = new Project(['id' => 1, 'name' => 'New Name']);
        $data = ['name' => 'New Name'];

        $this->projectRepository
            ->shouldReceive('update')
            ->with($project, $data)
            ->once()
            ->andReturn($updatedProject);

        $this->cacheService
            ->shouldReceive('invalidateProject')
            ->with($updatedProject)
            ->once();

        $result = $this->projectService->updateProject($project, $data);

        $this->assertEquals($updatedProject, $result);
    }

    public function test_delete_project_calls_repository_and_invalidates_cache(): void
    {
        $project = Mockery::mock(Project::class)->makePartial();
        $project->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);

        $this->projectRepository
            ->shouldReceive('delete')
            ->with($project)
            ->once()
            ->andReturn(true);

        $this->cacheService
            ->shouldReceive('invalidateProjectWithTasks')
            ->with(1)
            ->once();

        $result = $this->projectService->deleteProject($project);

        $this->assertTrue($result);
    }
}

