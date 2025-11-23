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
     * @OA\Get(
     *     path="/api/users",
     *     summary="List users",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, ref="#/components/responses/CollectionResponse"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = Cache::remember('users:list', config('cache_ttl.lists'), function () {
            return User::with(['tasks', 'createdTasks'])->get();
        });

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users)
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Create user",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UserInput")),
     *     @OA\Response(response=201, ref="#/components/responses/CreatedResponse"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $user = User::create($request->validated());

        Cache::forget('users:list');

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => new UserResource($user)
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     summary="Get user",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, ref="#/components/responses/ItemResponse"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

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
     * @OA\Put(
     *     path="/api/users/{id}",
     *     summary="Update user",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UserInput")),
     *     @OA\Response(response=200, ref="#/components/responses/UpdatedResponse"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $user->update($request->validated());

        Cache::forget("user:{$user->id}");
        Cache::forget("user:{$user->id}:workload");
        Cache::forget('users:list');

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => new UserResource($user)
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     summary="Delete user",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, ref="#/components/responses/DeletedResponse"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $userId = $user->id;
        $user->delete();

        Cache::forget("user:{$userId}");
        Cache::forget("user:{$userId}:workload");
        Cache::forget('users:list');

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{user}/workload",
     *     summary="Get user workload",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, ref="#/components/responses/ItemResponse"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function getWorkload(User $user): JsonResponse
    {
        $this->authorize('viewWorkload', $user);

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
