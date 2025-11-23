<?php

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

readonly class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private CacheService            $cacheService
    ) {}

    public function getAllUsers(): Collection
    {
        return $this->cacheService->rememberUsers(
            fn() => $this->userRepository->all()
        );
    }

    public function getUser(int $id): ?User
    {
        return $this->cacheService->rememberUser(
            $id,
            fn() => $this->userRepository->find($id)
        );
    }

    public function createUser(array $data): User
    {
        $user = $this->userRepository->create($data);
        $this->cacheService->invalidateUser($user);

        return $user;
    }

    public function updateUser(User $user, array $data): User
    {
        $user = $this->userRepository->update($user, $data);
        $this->cacheService->invalidateUser($user);

        return $user;
    }

    public function deleteUser(User $user): bool
    {
        $userId = $user->id;
        $result = $this->userRepository->delete($user);

        if ($result) {
            $this->cacheService->invalidateUser($user);
        }

        return $result;
    }

    public function getUserWorkload(User $user): array
    {
        return $this->cacheService->rememberUserWorkload(
            $user->id,
            fn() => $this->userRepository->getWorkload($user)
        );
    }
}
