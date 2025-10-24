<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

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
            'workload' => 50,
            'max_workload' => 100
        ];

        $user = User::create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('user', $user->role);
        $this->assertEquals(['PHP', 'Laravel'], $user->skills);
        $this->assertEquals(50, $user->workload);
        $this->assertEquals(100, $user->max_workload);
    }

    /**
     * Тест валидации email
     */
    public function test_user_email_must_be_unique(): void
    {
        User::create([
            'name' => 'First User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user'
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::create([
            'name' => 'Second User',
            'email' => 'test@example.com', // Дублирующий email
            'password' => 'password123',
            'role' => 'user'
        ]);
    }

    /**
     * Тест автоматического хеширования пароля
     */
    public function test_password_is_automatically_hashed(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user'
        ]);

        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(\Hash::check('password123', $user->password));
    }

    /**
     * Тест связи с задачами
     */
    public function test_user_has_many_tasks(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user'
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->tasks());
    }

    /**
     * Тест связи с созданными задачами
     */
    public function test_user_has_many_created_tasks(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user'
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->createdTasks());
    }

    /**
     * Тест связи с логами задач
     */
    public function test_user_has_many_task_logs(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user'
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->taskLogs());
    }

    /**
     * Тест проверки роли пользователя
     */
    public function test_user_role_validation(): void
    {
        $validRoles = ['admin', 'manager', 'user'];

        foreach ($validRoles as $role) {
            $user = User::create([
                'name' => 'Test User',
                'email' => "test{$role}@example.com",
                'password' => 'password123',
                'role' => $role
            ]);

            $this->assertEquals($role, $user->role);
        }
    }

    /**
     * Тест массового присвоения (fillable)
     */
    public function test_user_fillable_attributes(): void
    {
        $user = new User();
        $fillable = $user->getFillable();

        $expectedFillable = [
            'name',
            'email',
            'password',
            'role',
            'skills',
            'workload',
            'max_workload'
        ];

        $this->assertEquals($expectedFillable, $fillable);
    }

    /**
     * Тест скрытых атрибутов (hidden)
     */
    public function test_user_hidden_attributes(): void
    {
        $user = new User();
        $hidden = $user->getHidden();

        $expectedHidden = [
            'password',
            'remember_token'
        ];

        $this->assertEquals($expectedHidden, $hidden);
    }
}
