<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Tag(
 *     name="Tasks",
 *     description="Управление задачами"
 * )
 */
class TaskController extends Controller
{
    /**
     * @OA\Get(path="/api/tasks", summary="Список задач", tags={"Tasks"},
     *     @OA\Response(response=200, ref="#/components/responses/CollectionResponse")
     * )
     */
    public function index(): JsonResponse
    {
        $tasks = Cache::remember('tasks:list', config('cache_ttl.lists'), function () {
            return Task::with(['project', 'assignedUser', 'creator'])->get();
        });

        return response()->json([
            'success' => true,
            'data' => TaskResource::collection($tasks)
        ]);
    }

    /**
     * @OA\Post(path="/api/tasks", summary="Создать задачу", tags={"Tasks"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/TaskInput")),
     *     @OA\Response(response=201, ref="#/components/responses/CreatedResponse"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = Task::create($request->validated());

        Cache::forget('tasks:list');
        if ($task->project_id) {
            Cache::forget("tasks:project:{$task->project_id}");
        }
        if ($task->assigned_user_id) {
            Cache::forget("tasks:user:{$task->assigned_user_id}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Задача успешно создана',
            'data' => new TaskResource($task)
        ], 201);
    }

    /**
     * @OA\Get(path="/api/tasks/{id}", summary="Получить задачу", tags={"Tasks"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, ref="#/components/responses/ItemResponse"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(Task $task): JsonResponse
    {
        $cachedTask = Cache::remember("task:{$task->id}", config('cache_ttl.items'), function () use ($task) {
            $task->load(['project', 'assignedUser', 'creator', 'taskLogs.user']);
            return $task;
        });

        return response()->json([
            'success' => true,
            'data' => new TaskResource($cachedTask)
        ]);
    }

    /**
     * @OA\Put(path="/api/tasks/{id}", summary="Обновить задачу", tags={"Tasks"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/TaskInput")),
     *     @OA\Response(response=200, ref="#/components/responses/UpdatedResponse"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $oldProjectId = $task->project_id;
        $oldAssignedUserId = $task->assigned_user_id;

        $task->update($request->validated());

        Cache::forget("task:{$task->id}");
        Cache::forget('tasks:list');
        
        if ($oldProjectId) {
            Cache::forget("tasks:project:{$oldProjectId}");
        }
        if ($task->project_id && $task->project_id !== $oldProjectId) {
            Cache::forget("tasks:project:{$task->project_id}");
        }
        
        if ($oldAssignedUserId) {
            Cache::forget("tasks:user:{$oldAssignedUserId}");
        }
        if ($task->assigned_user_id && $task->assigned_user_id !== $oldAssignedUserId) {
            Cache::forget("tasks:user:{$task->assigned_user_id}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Задача успешно обновлена',
            'data' => new TaskResource($task)
        ]);
    }

    /**
     * @OA\Delete(path="/api/tasks/{id}", summary="Удалить задачу", tags={"Tasks"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, ref="#/components/responses/DeletedResponse"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(Task $task): JsonResponse
    {
        $projectId = $task->project_id;
        $assignedUserId = $task->assigned_user_id;
        $taskId = $task->id;

        $task->delete();

        Cache::forget("task:{$taskId}");
        Cache::forget('tasks:list');
        if ($projectId) {
            Cache::forget("tasks:project:{$projectId}");
        }
        if ($assignedUserId) {
            Cache::forget("tasks:user:{$assignedUserId}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Задача успешно удалена'
        ]);
    }

    /**
     * @OA\Get(path="/api/tasks/user/{user}", summary="Задачи пользователя", tags={"Tasks"},
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, ref="#/components/responses/CollectionResponse"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function getUserTasks(User $user): JsonResponse
    {
        $tasks = Cache::remember("tasks:user:{$user->id}", config('cache_ttl.lists'), function () use ($user) {
            return Task::where('assigned_user_id', $user->id)
                ->with(['project', 'creator'])
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => TaskResource::collection($tasks)
        ]);
    }

    /**
     * @OA\Get(path="/api/tasks/project/{project}", summary="Задачи проекта", tags={"Tasks"},
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, ref="#/components/responses/CollectionResponse"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function getProjectTasks(Project $project): JsonResponse
    {
        $tasks = Cache::remember("tasks:project:{$project->id}", config('cache_ttl.lists'), function () use ($project) {
            return Task::where('project_id', $project->id)
                ->with(['assignedUser', 'creator'])
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => TaskResource::collection($tasks)
        ]);
    }

    /**
     * @OA\Post(path="/api/tasks/{task}/assign", summary="Назначить задачу", tags={"Tasks"},
     *     @OA\Parameter(name="task", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"assigned_user_id"},
     *         @OA\Property(property="assigned_user_id", type="integer", example=1)
     *     )),
     *     @OA\Response(response=200, ref="#/components/responses/UpdatedResponse"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function assignTask(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'assigned_user_id' => 'required|exists:users,id'
        ]);

        $oldAssignedUserId = $task->assigned_user_id;
        $task->update($validated);

        Cache::forget("task:{$task->id}");
        Cache::forget('tasks:list');
        if ($oldAssignedUserId) {
            Cache::forget("tasks:user:{$oldAssignedUserId}");
        }
        if ($task->assigned_user_id) {
            Cache::forget("tasks:user:{$task->assigned_user_id}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Задача успешно назначена',
            'data' => new TaskResource($task->load(['assignedUser']))
        ]);
    }
}
