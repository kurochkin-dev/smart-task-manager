<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\SkillController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::apiResource('users', UserController::class);
    Route::get('/users/{user}/workload', [UserController::class, 'getWorkload']);

    Route::apiResource('projects', ProjectController::class);

    Route::apiResource('tasks', TaskController::class);
    Route::get('/tasks/user/{user}', [TaskController::class, 'getUserTasks']);
    Route::get('/tasks/project/{project}', [TaskController::class, 'getProjectTasks']);
    Route::post('/tasks/{task}/assign', [TaskController::class, 'assignTask']);

    Route::apiResource('skills', SkillController::class);
});
