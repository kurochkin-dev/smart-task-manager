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
     * @OA\Get(path="/api/projects", summary="Список проектов", tags={"Projects"},
     *     @OA\Response(response=200, ref="#/components/responses/CollectionResponse")
     * )
     */
    public function index(): JsonResponse
    {
        $projects = Cache::remember('projects:list', config('cache_ttl.lists'), function () {
            return Project::with(['tasks'])->get();
        });

        return response()->json([
            'success' => true,
            'data' => ProjectResource::collection($projects)
        ]);
    }

    /**
     * @OA\Post(path="/api/projects", summary="Создать проект", tags={"Projects"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ProjectInput")),
     *     @OA\Response(response=201, ref="#/components/responses/CreatedResponse"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = Project::create($request->validated());

        Cache::forget('projects:list');

        return response()->json([
            'success' => true,
            'message' => 'Проект успешно создан',
            'data' => new ProjectResource($project)
        ], 201);
    }

    /**
     * @OA\Get(path="/api/projects/{id}", summary="Получить проект", tags={"Projects"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, ref="#/components/responses/ItemResponse"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(Project $project): JsonResponse
    {
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
     * @OA\Put(path="/api/projects/{id}", summary="Обновить проект", tags={"Projects"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ProjectInput")),
     *     @OA\Response(response=200, ref="#/components/responses/UpdatedResponse"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $project->update($request->validated());

        Cache::forget("project:{$project->id}");
        Cache::forget("tasks:project:{$project->id}");
        Cache::forget('projects:list');

        return response()->json([
            'success' => true,
            'message' => 'Проект успешно обновлен',
            'data' => new ProjectResource($project)
        ]);
    }

    /**
     * @OA\Delete(path="/api/projects/{id}", summary="Удалить проект", tags={"Projects"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, ref="#/components/responses/DeletedResponse"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(Project $project): JsonResponse
    {
        $projectId = $project->id;
        $project->delete();

        Cache::forget("project:{$projectId}");
        Cache::forget("tasks:project:{$projectId}");
        Cache::forget('projects:list');
        Cache::forget('tasks:list');

        return response()->json([
            'success' => true,
            'message' => 'Проект успешно удален'
        ]);
    }
}
