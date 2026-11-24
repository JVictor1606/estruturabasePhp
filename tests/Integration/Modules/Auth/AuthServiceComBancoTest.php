<?php

declare(strict_types=1);

namespace Tests\Integration\Modules\Auth;

use Modular\Modules\Auth\Services\AuthService;
use Modular\Modules\Usuarios\Domain\UsuarioException;
use Modular\Modules\Usuarios\Infrastructure\UserRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Support\DatabaseTestCase;
use Tests\Support\UserFactory;

/** AuthService + UserRepository reais, contra o MySQL de teste. */
#[CoversClass(AuthService::class)]
#[CoversClass(UserRepository::class)]
final class AuthServiceComBancoTest extends DatabaseTestCase
{
    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $this->service = new AuthService($this->repositorio());
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testAutenticaUsuarioGravadoNoBanco(): void
    {
        $gravado = $this->repositorio()->createUser(UserFactory::comum('ana@exemplo.com', 'Senha@123'));

        $this->assertSame($gravado->getId(), $this->service->auth('ana@exemplo.com', 'Senha@123')->getId());
    }

    public function testRecusaSenhaErradaParaUsuarioDoBanco(): void
    {
        $this->repositorio()->createUser(UserFactory::comum('ana@exemplo.com', 'Senha@123'));

        $this->expectException(UsuarioException::class);
        $this->service->auth('ana@exemplo.com', 'errada');
    }

    public function testRecusaUsuarioDesativadoNoBanco(): void
    {
        $this->repositorio()->createUser(UserFactory::inativo('ana@exemplo.com', 'Senha@123'));

        $this->expectException(UsuarioException::class);
        $this->service->auth('ana@exemplo.com', 'Senha@123');
    }

    public function testSessaoDeUsuarioDesativadoDepoisDoLoginDeixaDeValer(): void
    {
        $user = $this->repositorio()->createUser(UserFactory::comum('ana@exemplo.com'));
        $_SESSION['user_id'] = $user->getId();
        $this->assertTrue($this->service->checkLogin());

        $user->setStatus(\Modular\Modules\Usuarios\Domain\Status::INATIVO);
        $this->repositorio()->updateUser($user);

        $this->assertFalse($this->service->checkLogin());
    }
}
