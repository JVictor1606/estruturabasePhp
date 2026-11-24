<?php

declare(strict_types=1);

namespace Src\App\Services;

use Src\App\Infrastructure\IRepositories\IUserRepository;
use Src\App\Enums\Perfil;
use Src\App\ValueObjects\Senha;
use Src\App\Models\User;
use Src\App\Http\Exceptions\Usuario\UsuarioException;
use Src\App\Services\IServices\IAuthService;

class AuthService implements IAuthService
{
    /**
     * Hash bcrypt (custo 12, o mesmo dos usuários reais) de uma senha aleatória descartada.
     * Verificado quando o e-mail não existe, para que "e-mail inexistente" e "senha errada"
     * levem o mesmo tempo e o tempo de resposta não revele quais e-mails estão cadastrados.
     * Precisa ser constante: gerar na hora deixaria o caminho do e-mail inexistente MAIS lento.
     */
    private const HASH_ISCA = '$2y$12$dvxYgV8ZKDU1jHUBIpK2gOzzHfdx8ZYjKM1nm2lYBIfvX.4A4yeJe';

    private IUserRepository $repository;

    public function __construct(IUserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function auth(string $email, string $senha): User
    {
        $user = $this->repository->getUserByEmail($email);

        $senhaConfere = $user !== null
            ? $user->verificaSenha($senha)
            : Senha::senhaHash(self::HASH_ISCA)->confere($senha);

        if ($user === null || ! $senhaConfere || ! $user->isAtivo()) {
            throw UsuarioException::credenciaisInvalidas();
        }

        return $user;
    }

    public function checkLogin(): bool
    {
        if (! isset($_SESSION['user_id'])) {
            return false;
        }

        $user = $this->repository->getUserById((string) $_SESSION['user_id']);
        if ($user === null || ! $user->isAtivo()) {
            $this->logout();
            return false;
        }

        return true;
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function checkAdmin(): bool
    {
        return ($_SESSION['usuario_role'] ?? null) === Perfil::ADMIN->value;
    }
}
