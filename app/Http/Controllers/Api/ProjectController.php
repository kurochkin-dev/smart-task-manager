<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    /**
     * Получить список всех проектов
     * GET /api/projects
     */
    public function index(): JsonResponse
    {
        $projects = Project::with(['tasks'])->get();

        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    /**
     * Создать новый проект
     * POST /api/projects
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:active,completed,paused',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $project = Project::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Проект успешно создан',
            'data' => $project
        ], 201);
    }

    /**
     * Получить конкретный проект
     * GET /api/projects/{id}
     */
    public function show(Project $project): JsonResponse
    {
        $project->load(['tasks.assignedUser', 'tasks.creator']);

        return response()->json([
            'success' => true,
            'data' => $project
        ]);
    }

    /**
     * Обновить проект
     * PUT/PATCH /api/projects/{id}
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,completed,paused',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $project->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Проект успешно обновлен',
            'data' => $project
        ]);
    }

    /**
     * Удалить проект
     * DELETE /api/projects/{id}
     */
    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Проект успешно удален'
        ]);
    }
}
