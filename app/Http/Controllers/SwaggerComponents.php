<?php

namespace App\Http\Controllers;

/**
 * Общие компоненты для Swagger документации
 * 
 * @OA\Components(
 *     @OA\Schema(
 *         schema="User",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="John Doe"),
 *         @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *         @OA\Property(property="role", type="string", enum={"admin", "manager", "user"}, example="user"),
 *         @OA\Property(property="skills", type="array", @OA\Items(type="string"), example={"PHP", "Laravel"}),
 *         @OA\Property(property="workload", type="integer", example=50),
 *         @OA\Property(property="max_workload", type="integer", example=100),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time")
 *     ),
 *     @OA\Schema(
 *         schema="UserInput",
 *         type="object",
 *         required={"name", "email", "password", "role"},
 *         @OA\Property(property="name", type="string", example="John Doe"),
 *         @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *         @OA\Property(property="password", type="string", format="password", example="password123"),
 *         @OA\Property(property="role", type="string", enum={"admin", "manager", "user"}, example="user"),
 *         @OA\Property(property="skills", type="array", @OA\Items(type="string"), example={"PHP", "Laravel"}),
 *         @OA\Property(property="max_workload", type="integer", example=100)
 *     ),
 *     @OA\Schema(
 *         schema="Task",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="title", type="string", example="Настройка API"),
 *         @OA\Property(property="description", type="string", example="Создание REST API для задач"),
 *         @OA\Property(property="status", type="string", enum={"pending", "in_progress", "completed", "cancelled"}, example="pending"),
 *         @OA\Property(property="priority", type="string", enum={"low", "medium", "high", "urgent"}, example="high"),
 *         @OA\Property(property="estimated_hours", type="integer", example=8),
 *         @OA\Property(property="actual_hours", type="integer", example=0),
 *         @OA\Property(property="required_skills", type="array", @OA\Items(type="string"), example={"PHP", "Laravel"}),
 *         @OA\Property(property="complexity", type="integer", example=3),
 *         @OA\Property(property="due_date", type="string", format="date", example="2024-12-31"),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time")
 *     ),
 *     @OA\Schema(
 *         schema="TaskInput",
 *         type="object",
 *         required={"title", "project_id", "created_by"},
 *         @OA\Property(property="title", type="string", example="Настройка API"),
 *         @OA\Property(property="description", type="string", example="Создание REST API для задач"),
 *         @OA\Property(property="priority", type="string", enum={"low", "medium", "high", "urgent"}, example="high"),
 *         @OA\Property(property="estimated_hours", type="integer", example=8),
 *         @OA\Property(property="required_skills", type="array", @OA\Items(type="string"), example={"PHP", "Laravel"}),
 *         @OA\Property(property="complexity", type="integer", example=3),
 *         @OA\Property(property="due_date", type="string", format="date", example="2024-12-31"),
 *         @OA\Property(property="project_id", type="integer", example=1),
 *         @OA\Property(property="assigned_user_id", type="integer", example=1),
 *         @OA\Property(property="created_by", type="integer", example=1)
 *     ),
 *     @OA\Schema(
 *         schema="Project",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Smart Task Manager"),
 *         @OA\Property(property="description", type="string", example="Проект для управления задачами"),
 *         @OA\Property(property="status", type="string", enum={"active", "completed", "paused"}, example="active"),
 *         @OA\Property(property="start_date", type="string", format="date", example="2024-01-01"),
 *         @OA\Property(property="end_date", type="string", format="date", example="2024-12-31"),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time")
 *     ),
 *     @OA\Schema(
 *         schema="ProjectInput",
 *         type="object",
 *         required={"name"},
 *         @OA\Property(property="name", type="string", example="Smart Task Manager"),
 *         @OA\Property(property="description", type="string", example="Проект для управления задачами"),
 *         @OA\Property(property="status", type="string", enum={"active", "completed", "paused"}, example="active"),
 *         @OA\Property(property="start_date", type="string", format="date", example="2024-01-01"),
 *         @OA\Property(property="end_date", type="string", format="date", example="2024-12-31")
 *     ),
 *     @OA\Schema(
 *         schema="SuccessResponse",
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="message", type="string", example="Операция выполнена успешно")
 *     ),
 *     @OA\Schema(
 *         schema="DataResponse",
 *         type="object",
 *         @OA\Property(property="success", type="boolean", example=true),
 *         @OA\Property(property="data", type="object")
 *     ),
 *     @OA\Response(
 *         response="Success",
 *         description="Успешный ответ",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true)
 *         )
 *     ),
 *     @OA\Response(
 *         response="NotFound",
 *         description="Ресурс не найден",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Ресурс не найден")
 *         )
 *     ),
 *     @OA\Response(
 *         response="ValidationError",
 *         description="Ошибка валидации",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Ошибка валидации"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response="CollectionResponse",
 *         description="Список ресурсов",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     ),
 *     @OA\Response(
 *         response="ItemResponse",
 *         description="Один ресурс",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response="CreatedResponse",
 *         description="Ресурс создан",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Ресурс успешно создан"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response="UpdatedResponse",
 *         description="Ресурс обновлен",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Ресурс успешно обновлен"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response="DeletedResponse",
 *         description="Ресурс удален",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Ресурс успешно удален")
 *         )
 *     )
 * )
 */
class SwaggerComponents
{
}

