<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    /**
     * Получить список всех задач
     * GET /api/tasks
     */
    public function index(): JsonResponse
    {
        $tasks = Task::with(['project', 'assignedUser', 'creator'])->get();

        return response()->json([
            'success' => true,
            'data' => $tasks
        ]);
    }

    /**
     * Создать новую задачу
     * POST /api/tasks
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'in:low,medium,high,urgent',
            'estimated_hours' => 'nullable|integer|min:1',
            'required_skills' => 'nullable|array',
            'complexity' => 'integer|min:1|max:5',
            'due_date' => 'nullable|date',
            'project_id' => 'required|exists:projects,id',
            'assigned_user_id' => 'nullable|exists:users,id',
            'created_by' => 'required|exists:users,id',
        ]);

        $task = Task::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Задача успешно создана',
            'data' => $task
        ], 201);
    }

    /**
     * Получить конкретную задачу
     * GET /api/tasks/{id}
     */
    public function show(Task $task): JsonResponse
    {
        $task->load(['project', 'assignedUser', 'creator', 'taskLogs.user']);

        return response()->json([
            'success' => true,
            'data' => $task
        ]);
    }

    /**
     * Обновить задачу
     * PUT/PATCH /api/tasks/{id}
     */
    public function update(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:pending,in_progress,completed,cancelled',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'estimated_hours' => 'nullable|integer|min:1',
            'actual_hours' => 'integer|min:0',
            'required_skills' => 'nullable|array',
            'complexity' => 'integer|min:1|max:5',
            'due_date' => 'nullable|date',
            'assigned_user_id' => 'nullable|exists:users,id',
        ]);

        $task->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Задача успешно обновлена',
            'data' => $task
        ]);
    }

    /**
     * Удалить задачу
     * DELETE /api/tasks/{id}
     */
    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Задача успешно удалена'
        ]);
    }
}
