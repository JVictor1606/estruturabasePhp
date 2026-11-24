<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Auth\Services;

use Modular\Modules\Auth\Services\AuthService;
use Modular\Modules\Usuarios\Domain\Perfil;
use Modular\Modules\Usuarios\Domain\Senha;
use Modular\Modules\Usuarios\Domain\Status;
use Modular\Modules\Usuarios\Domain\User;
use Modular\Modules\Usuarios\Domain\UsuarioException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeUserRepository;

/**
 * Enumeração de usuários por tempo de resposta: e-mail inexistente não pode responder
 * muito mais rápido que senha errada de e-mail existente (o bcrypt dominaria a diferença).
 */
#[CoversClass(AuthService::class)]
#[Group('seguranca')]
final class AuthServiceTempoTest extends TestCase
{
    public function testEmailInexistenteGastaTempoComparavelAoDeSenhaErrada(): void
    {
        $repositorio = new FakeUserRepository();
        // Hash real, com o custo de produção: é ele que torna a diferença mensurável.
        $repositorio->createUser(new User('Ana', 'ana@exemplo.com', Senha::senha('Senha@123'), Perfil::USER, Status::ATIVO));
        $service = new AuthService($repositorio);

        $senhaErrada = $this->medir(fn () => $service->auth('ana@exemplo.com', 'Errada@123'));
        $inexistente = $this->medir(fn () => $service->auth('ninguem@exemplo.com', 'Errada@123'));

        $this->assertGreaterThan(
            $senhaErrada * 0.5,
            $inexistente,
            sprintf('E-mail inexistente respondeu em %.0f ms contra %.0f ms da senha errada: dá para descobrir quais e-mails existem.', $inexistente * 1000, $senhaErrada * 1000)
        );
    }

    private function medir(callable $acao): float
    {
        $inicio = microtime(true);
        try {
            $acao();
        } catch (UsuarioException) {
        }

        return microtime(true) - $inicio;
    }
}
