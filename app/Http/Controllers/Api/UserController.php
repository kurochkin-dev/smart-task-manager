<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Tag(
 *     name="Users",
 *     description="Управление пользователями"
 * )
 */
class UserController extends Controller
{
    /**
     * @OA\Get(path="/api/users", summary="Список пользователей", tags={"Users"},
     *     @OA\Response(response=200, ref="#/components/responses/CollectionResponse")
     * )
     */
    public function index(): JsonResponse
    {
        $users = Cache::remember('users:list', config('cache_ttl.lists'), function () {
            return User::with(['tasks', 'createdTasks'])->get();
        });

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users)
        ]);
    }

    /**
     * @OA\Post(path="/api/users", summary="Создать пользователя", tags={"Users"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UserInput")),
     *     @OA\Response(response=201, ref="#/components/responses/CreatedResponse"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        Cache::forget('users:list');

        return response()->json([
            'success' => true,
            'message' => 'Пользователь успешно создан',
            'data' => new UserResource($user)
        ], 201);
    }

    /**
     * @OA\Get(path="/api/users/{id}", summary="Получить пользователя", tags={"Users"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, ref="#/components/responses/ItemResponse"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(User $user): JsonResponse
    {
        $cachedUser = Cache::remember("user:{$user->id}", config('cache_ttl.items'), function () use ($user) {
            $user->load(['tasks', 'createdTasks', 'taskLogs']);
            return $user;
        });

        return response()->json([
            'success' => true,
            'data' => new UserResource($cachedUser)
        ]);
    }

    /**
     * @OA\Put(path="/api/users/{id}", summary="Обновить пользователя", tags={"Users"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UserInput")),
     *     @OA\Response(response=200, ref="#/components/responses/UpdatedResponse"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());

        Cache::forget("user:{$user->id}");
        Cache::forget("user:{$user->id}:workload");
        Cache::forget('users:list');

        return response()->json([
            'success' => true,
            'message' => 'Пользователь успешно обновлен',
            'data' => new UserResource($user)
        ]);
    }

    /**
     * @OA\Delete(path="/api/users/{id}", summary="Удалить пользователя", tags={"Users"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, ref="#/components/responses/DeletedResponse"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(User $user): JsonResponse
    {
        $userId = $user->id;
        $user->delete();

        Cache::forget("user:{$userId}");
        Cache::forget("user:{$userId}:workload");
        Cache::forget('users:list');

        return response()->json([
            'success' => true,
            'message' => 'Пользователь успешно удален'
        ]);
    }

    /**
     * @OA\Get(path="/api/users/{user}/workload", summary="Загруженность пользователя", tags={"Users"},
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, ref="#/components/responses/ItemResponse"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function getWorkload(User $user): JsonResponse
    {
        $workload = Cache::remember("user:{$user->id}:workload", config('cache_ttl.workload'), function () use ($user) {
            $usagePercentage = $user->max_workload > 0 
                ? ($user->workload / $user->max_workload) * 100 
                : 0;

            return [
                'user_id' => $user->id,
                'current_workload' => $user->workload,
                'max_workload' => $user->max_workload,
                'usage_percentage' => round($usagePercentage, 2)
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $workload
        ]);
    }
}
