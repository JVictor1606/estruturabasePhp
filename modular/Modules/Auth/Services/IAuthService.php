<?php

declare(strict_types=1);

namespace Modular\Modules\Auth\Services;

use Modular\Modules\Usuarios\Domain\User;

interface IAuthService
{
    public function auth(string $email, string $senha): User;
    public function checkLogin(): bool;
    public function logout(): void;
    public function checkAdmin(): bool;
}
