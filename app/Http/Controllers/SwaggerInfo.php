<?php

namespace App\Http\Controllers;

/**
 * Базовая информация об API для Swagger
 * 
 * @OA\Info(
 *     title="Smart Task Manager API",
 *     version="1.0.0",
 *     description="API для управления задачами с интеллектуальным распределением между пользователями"
 * )
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Локальный сервер"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class SwaggerInfo
{
}

