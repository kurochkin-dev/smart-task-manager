<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Получить список всех пользователей
     * GET /api/users
     */
    public function index(): JsonResponse
    {
        $users = User::with(['tasks', 'createdTasks'])->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Создать нового пользователя
     * POST /api/users
     */
    public function store(Request $request): JsonResponse
    {
        // Валидация входных данных
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['admin', 'manager', 'user'])],
            'skills' => 'nullable|array',
            'max_workload' => 'integer|min:1|max:200'
        ]);

        // Создание пользователя
        $user = User::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Пользователь успешно создан',
            'data' => $user
        ], 201);
    }

    /**
     * Получить конкретного пользователя
     * GET /api/users/{id}
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['tasks', 'createdTasks', 'taskLogs']);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Обновить пользователя
     * PUT/PATCH /api/users/{id}
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:8',
            'role' => ['sometimes', Rule::in(['admin', 'manager', 'user'])],
            'skills' => 'nullable|array',
            'workload' => 'integer|min:0',
            'max_workload' => 'integer|min:1|max:200'
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Пользователь успешно обновлен',
            'data' => $user
        ]);
    }

    /**
     * Удалить пользователя
     * DELETE /api/users/{id}
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Пользователь успешно удален'
        ]);
    }
}
