<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Models\User;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_users_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

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
                    ]
                ]
            ])
            ->assertJson(['success' => true]);
    }

    public function test_can_create_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user'
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
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'User created successfully'
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    public function test_can_get_specific_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ]
            ]);
    }

    public function test_can_update_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $updateData = [
            'name' => 'Updated Name',
            'email' => $user->email,
            'role' => 'manager',
        ];

        $response = $this->putJson("/api/users/{$user->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => [
                    'name' => 'Updated Name',
                    'role' => 'manager',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'role' => 'manager',
        ]);
    }

    public function test_can_delete_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $user = User::factory()->create();

        $response = $this->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_can_get_user_workload(): void
    {
        $user = User::factory()->create([
            'workload' => 50,
            'max_workload' => 100,
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/users/{$user->id}/workload");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_id',
                    'current_workload',
                    'max_workload',
                    'usage_percentage',
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'current_workload' => 50,
                    'max_workload' => 100,
                    'usage_percentage' => 50.0,
                ]
            ]);
    }

    public function test_returns_404_for_nonexistent_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/users/99999');

        $response->assertStatus(404);
    }

    public function test_validation_fails_for_invalid_user_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'role' => 'invalid-role',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/users');

        $response->assertStatus(401);
    }

    public function test_user_role_cannot_create_users(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user'
        ]);

        $response->assertStatus(403);
    }

    public function test_user_role_cannot_delete_users(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user);

        $otherUser = User::factory()->create();

        $response = $this->deleteJson("/api/users/{$otherUser->id}");

        $response->assertStatus(403);
    }
}
