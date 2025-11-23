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
        $tasks = Task::with(['project', 'assignedUser', 'creator'])->get();

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
        $task->load(['project', 'assignedUser', 'creator', 'taskLogs.user']);

        return response()->json([
            'success' => true,
            'data' => new TaskResource($task)
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
        $task->update($request->validated());

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
        $task->delete();

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
        $tasks = Task::where('assigned_user_id', $user->id)
            ->with(['project', 'creator'])
            ->get();

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
        $tasks = Task::where('project_id', $project->id)
            ->with(['assignedUser', 'creator'])
            ->get();

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

        $task->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Задача успешно назначена',
            'data' => new TaskResource($task->load(['assignedUser']))
        ]);
    }
}
