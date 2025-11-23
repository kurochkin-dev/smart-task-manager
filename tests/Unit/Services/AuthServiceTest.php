<?php

namespace Tests\Unit\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    private UserRepositoryInterface $userRepository;
    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->authService = new AuthService($this->userRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_register_creates_user_and_returns_token(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user'
        ];

        $user = Mockery::mock(User::class);
        $user->shouldReceive('createToken')
            ->with('auth_token')
            ->once()
            ->andReturn((object)['plainTextToken' => 'test-token']);

        $this->userRepository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($arg) use ($data) {
                return $arg['name'] === $data['name']
                    && $arg['email'] === $data['email']
                    && $arg['role'] === $data['role']
                    && $arg['workload'] === 0
                    && $arg['max_workload'] === 100;
            }))
            ->andReturn($user);

        $result = $this->authService->register($data);

        $this->assertEquals($user, $result['user']);
        $this->assertEquals('test-token', $result['token']);
    }

    public function test_register_uses_default_role_if_not_provided(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $user = Mockery::mock(User::class);
        $user->shouldReceive('createToken')
            ->andReturn((object)['plainTextToken' => 'test-token']);

        $this->userRepository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($arg) {
                return $arg['role'] === 'user';
            }))
            ->andReturn($user);

        $this->authService->register($data);
    }

    public function test_login_returns_user_and_token_with_valid_credentials(): void
    {
        Hash::shouldReceive('check')
            ->with('password123', 'hashed_password')
            ->once()
            ->andReturn(true);

        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getAttribute')
            ->with('password')
            ->andReturn('hashed_password');
        $user->shouldReceive('createToken')
            ->with('auth_token')
            ->once()
            ->andReturn((object)['plainTextToken' => 'test-token']);

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->with('test@example.com')
            ->once()
            ->andReturn($user);

        $result = $this->authService->login('test@example.com', 'password123');

        $this->assertNotNull($result);
        $this->assertEquals($user, $result['user']);
        $this->assertEquals('test-token', $result['token']);
    }

    public function test_login_returns_null_when_user_not_found(): void
    {
        $this->userRepository
            ->shouldReceive('findByEmail')
            ->with('nonexistent@example.com')
            ->once()
            ->andReturn(null);

        $result = $this->authService->login('nonexistent@example.com', 'password123');

        $this->assertNull($result);
    }

    public function test_login_returns_null_with_invalid_password(): void
    {
        Hash::shouldReceive('check')
            ->with('wrong_password', 'hashed_password')
            ->once()
            ->andReturn(false);

        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getAttribute')
            ->with('password')
            ->andReturn('hashed_password');

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->with('test@example.com')
            ->once()
            ->andReturn($user);

        $result = $this->authService->login('test@example.com', 'wrong_password');

        $this->assertNull($result);
    }
}

