<?php

declare(strict_types=1);

namespace Tests\Support;

use Src\App\Infrastructure\IRepositories\IUserRepository;
use Src\App\Models\User;

/** Repositório em memória: permite testar serviços sem banco. */
final class FakeUserRepository implements IUserRepository
{
    /** @var array<string, User> */
    private array $users = [];

    public function createUser(User $newUser): User
    {
        $this->users[$newUser->getId()] = $newUser;

        return $newUser;
    }

    public function updateUser(User $user): User
    {
        $this->users[$user->getId()] = $user;

        return $user;
    }

    public function deleteUser(string $id): bool
    {
        $existia = isset($this->users[$id]);
        unset($this->users[$id]);

        return $existia;
    }

    public function getAllUser(): array
    {
        return array_values($this->users);
    }

    public function getUserById(string $id): ?User
    {
        return $this->users[$id] ?? null;
    }

    public function getUserByEmail(string $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->getEmail() === $email) {
                return $user;
            }
        }

        return null;
    }

    public function getAllUsersPaginado(int $limit, int $start): array
    {
        return array_slice(array_values($this->users), $start, $limit);
    }

    public function countUsers(): int
    {
        return count($this->users);
    }
}
