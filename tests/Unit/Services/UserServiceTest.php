<?php

namespace Tests\Unit\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Services\CacheService;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    private UserRepositoryInterface $userRepository;
    private CacheService $cacheService;
    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->cacheService = Mockery::mock(CacheService::class);
        $this->userService = new UserService($this->userRepository, $this->cacheService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_all_users_calls_repository_and_cache(): void
    {
        $users = new Collection([new User()]);

        $this->cacheService
            ->shouldReceive('rememberUsers')
            ->once()
            ->andReturn($users);

        $result = $this->userService->getAllUsers();

        $this->assertEquals($users, $result);
    }

    public function test_get_user_calls_repository_and_cache(): void
    {
        $user = new User(['id' => 1]);

        $this->cacheService
            ->shouldReceive('rememberUser')
            ->with(1, Mockery::type('callable'))
            ->once()
            ->andReturn($user);

        $result = $this->userService->getUser(1);

        $this->assertEquals($user, $result);
    }

    public function test_create_user_calls_repository_and_invalidates_cache(): void
    {
        $data = ['name' => 'Test User', 'email' => 'test@example.com'];
        $user = new User($data);

        $this->userRepository
            ->shouldReceive('create')
            ->with($data)
            ->once()
            ->andReturn($user);

        $this->cacheService
            ->shouldReceive('invalidateUser')
            ->with($user)
            ->once();

        $result = $this->userService->createUser($data);

        $this->assertEquals($user, $result);
    }

    public function test_update_user_calls_repository_and_invalidates_cache(): void
    {
        $user = new User(['id' => 1, 'name' => 'Old Name']);
        $updatedUser = new User(['id' => 1, 'name' => 'New Name']);
        $data = ['name' => 'New Name'];

        $this->userRepository
            ->shouldReceive('update')
            ->with($user, $data)
            ->once()
            ->andReturn($updatedUser);

        $this->cacheService
            ->shouldReceive('invalidateUser')
            ->with($updatedUser)
            ->once();

        $result = $this->userService->updateUser($user, $data);

        $this->assertEquals($updatedUser, $result);
    }

    public function test_delete_user_calls_repository_and_invalidates_cache(): void
    {
        $user = new User(['id' => 1]);

        $this->userRepository
            ->shouldReceive('delete')
            ->with($user)
            ->once()
            ->andReturn(true);

        $this->cacheService
            ->shouldReceive('invalidateUser')
            ->with($user)
            ->once();

        $result = $this->userService->deleteUser($user);

        $this->assertTrue($result);
    }

    public function test_get_user_workload_calls_repository_and_cache(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);

        $workload = [
            'user_id' => 1,
            'current_workload' => 50,
            'max_workload' => 100,
            'usage_percentage' => 50.0
        ];

        $this->cacheService
            ->shouldReceive('rememberUserWorkload')
            ->with(1, Mockery::type('callable'))
            ->once()
            ->andReturn($workload);

        $result = $this->userService->getUserWorkload($user);

        $this->assertEquals($workload, $result);
    }
}

