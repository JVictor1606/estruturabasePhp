<?php

declare(strict_types=1);

namespace Modular\Modules\Usuarios\Services;

use Modular\Modules\Usuarios\Domain\User;
use Modular\Modules\Usuarios\Dto\CriarUserDto;
use Modular\Modules\Usuarios\Dto\EditUserDto;

interface IUserService
{
    public function createUser(CriarUserDto $dto): User;
    public function deleteUser(string $id, ?string $usuarioLogadoId = null): void;
    public function updateUser(EditUserDto $dto, ?string $usuarioLogadoId = null): User;
    public function atualizarSenha(string $id, string $novaSenha): User;
    public function trocarSenha(string $id, string $senhaAtual, string $novaSenha): User;
    public function redefinirSenha(string $email, string $novaSenha): void;
    public function verificarSenhaAtual(string $id, string $senhaAtual): bool;
    public function getUserById(string $id): User;
    public function getUserByEmail(string $email): User;
    public function getAllUsers(): array;
    public function paginacao(): array;
}
