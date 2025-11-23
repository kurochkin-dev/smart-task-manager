<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Tag(
 *     name="Projects",
 *     description="Управление проектами"
 * )
 */
class ProjectController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/projects",
     *     summary="List projects",
     *     tags={"Projects"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/CollectionResponse"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        $projects = Cache::remember('projects:list', config('cache_ttl.lists'), function () {
            return Project::with(['tasks'])->get();
        });

        return response()->json([
            'success' => true,
            'data' => ProjectResource::collection($projects)
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/projects",
     *     summary="Create project",
     *     tags={"Projects"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ProjectInput")),
     *     @OA\Response(response=201, ref="#/components/responses/CreatedResponse"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $project = Project::create($request->validated());

        Cache::forget('projects:list');

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully',
            'data' => new ProjectResource($project)
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/projects/{id}",
     *     summary="Get project",
     *     tags={"Projects"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, ref="#/components/responses/ItemResponse"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $cachedProject = Cache::remember("project:{$project->id}", config('cache_ttl.items'), function () use ($project) {
            $project->load(['tasks.assignedUser', 'tasks.creator']);
            return $project;
        });

        return response()->json([
            'success' => true,
            'data' => new ProjectResource($cachedProject)
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/projects/{id}",
     *     summary="Update project",
     *     tags={"Projects"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ProjectInput")),
     *     @OA\Response(response=200, ref="#/components/responses/UpdatedResponse"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        Cache::forget("project:{$project->id}");
        Cache::forget("tasks:project:{$project->id}");
        Cache::forget('projects:list');

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully',
            'data' => new ProjectResource($project)
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/projects/{id}",
     *     summary="Delete project",
     *     tags={"Projects"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, ref="#/components/responses/DeletedResponse"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $projectId = $project->id;
        $project->delete();

        Cache::forget("project:{$projectId}");
        Cache::forget("tasks:project:{$projectId}");
        Cache::forget('projects:list');
        Cache::forget('tasks:list');

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully'
        ]);
    }
}
