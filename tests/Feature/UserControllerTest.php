<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class UserControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Тест получения списка пользователей
     */
    public function test_can_get_users_list(): void
    {
        // Создаем тестовых пользователей
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'skills',
                        'workload',
                        'max_workload',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ])
            ->assertJson(['success' => true]);
    }

    /**
     * Тест создания пользователя
     */
    public function test_can_create_user(): void
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user',
            'skills' => ['PHP', 'Laravel'],
            'max_workload' => 100
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'skills',
                    'workload',
                    'max_workload'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Пользователь успешно создан',
                'data' => [
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'role' => 'user'
                ]
            ]);

        // Проверяем, что пользователь создался в базе
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'user'
        ]);
    }

    /**
     * Тест создания пользователя с невалидными данными
     */
    public function test_cannot_create_user_with_invalid_data(): void
    {
        $userData = [
            'name' => '', // Пустое имя
            'email' => 'invalid-email', // Невалидный email
            'password' => '123', // Слишком короткий пароль
            'role' => 'invalid_role' // Невалидная роль
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
    }

    /**
     * Тест получения конкретного пользователя
     */
    public function test_can_get_specific_user(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'admin'
        ]);

        $response = $this->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'role' => 'admin'
                ]
            ]);
    }

    /**
     * Тест обновления пользователя
     */
    public function test_can_update_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'role' => 'user'
        ]);

        $updateData = [
            'name' => 'New Name',
            'role' => 'manager'
        ];

        $response = $this->putJson("/api/users/{$user->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Пользователь успешно обновлен',
                'data' => [
                    'name' => 'New Name',
                    'role' => 'manager'
                ]
            ]);

        // Проверяем, что данные обновились в базе
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'role' => 'manager'
        ]);
    }

    /**
     * Тест удаления пользователя
     */
    public function test_can_delete_user(): void
    {
        $user = User::factory()->create();

        $response = $this->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Пользователь успешно удален'
            ]);

        // Проверяем, что пользователь удален из базы
        $this->assertDatabaseMissing('users', [
            'id' => $user->id
        ]);
    }

    /**
     * Тест получения несуществующего пользователя
     */
    public function test_cannot_get_nonexistent_user(): void
    {
        $response = $this->getJson('/api/users/999999');

        $response->assertStatus(404);
    }
}
