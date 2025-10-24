<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\SkillController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('users', UserController::class);

Route::apiResource('projects', ProjectController::class);

Route::apiResource('tasks', TaskController::class);

Route::apiResource('skills', SkillController::class);

Route::get('/tasks/user/{user}', [TaskController::class, 'getUserTasks']);
Route::get('/tasks/project/{project}', [TaskController::class, 'getProjectTasks']);
Route::post('/tasks/{task}/assign', [TaskController::class, 'assignTask']);

Route::get('/users/{user}/workload', [UserController::class, 'getWorkload']);
