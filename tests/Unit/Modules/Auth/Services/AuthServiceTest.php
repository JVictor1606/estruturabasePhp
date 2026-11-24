<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Auth\Services;

use Modular\Modules\Auth\Services\AuthService;
use Modular\Modules\Usuarios\Domain\UsuarioException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeUserRepository;
use Tests\Support\UserFactory;

#[CoversClass(AuthService::class)]
#[CoversClass(UsuarioException::class)]
final class AuthServiceTest extends TestCase
{
    private FakeUserRepository $repositorio;
    private AuthService $service;

    protected function setUp(): void
    {
        $_SESSION = [];
        $this->repositorio = new FakeUserRepository();
        $this->service = new AuthService($this->repositorio);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testAutenticaComCredenciaisCorretas(): void
    {
        $user = $this->repositorio->createUser(UserFactory::comum('ana@exemplo.com', 'Senha@123'));

        $autenticado = $this->service->auth('ana@exemplo.com', 'Senha@123');

        $this->assertSame($user->getId(), $autenticado->getId());
    }

    public function testRecusaSenhaErrada(): void
    {
        $this->repositorio->createUser(UserFactory::comum('ana@exemplo.com', 'Senha@123'));

        $this->expectException(UsuarioException::class);
        $this->service->auth('ana@exemplo.com', 'errada');
    }

    public function testRecusaEmailInexistente(): void
    {
        $this->expectException(UsuarioException::class);
        $this->service->auth('ninguem@exemplo.com', 'Senha@123');
    }

    public function testRecusaUsuarioInativoMesmoComSenhaCorreta(): void
    {
        $this->repositorio->createUser(UserFactory::inativo('ana@exemplo.com', 'Senha@123'));

        $this->expectException(UsuarioException::class);
        $this->service->auth('ana@exemplo.com', 'Senha@123');
    }

    public function testMensagemDeErroNaoRevelaSeOEmailExiste(): void
    {
        $this->repositorio->createUser(UserFactory::comum('ana@exemplo.com', 'Senha@123'));
        $this->repositorio->createUser(UserFactory::inativo('inativo@exemplo.com', 'Senha@123'));

        $mensagens = [
            $this->mensagemDe('ana@exemplo.com', 'errada'),
            $this->mensagemDe('ninguem@exemplo.com', 'Senha@123'),
            $this->mensagemDe('inativo@exemplo.com', 'Senha@123'),
        ];

        $this->assertCount(1, array_unique($mensagens));
        $this->assertSame('Usuário ou senha incorretos.', $mensagens[0]);
    }

    public function testFalhaDeLoginRetornaStatus401(): void
    {
        try {
            $this->service->auth('ninguem@exemplo.com', 'Senha@123');
            $this->fail('Era esperada UsuarioException.');
        } catch (UsuarioException $e) {
            $this->assertSame(401, $e->getStatusCode());
        }
    }

    public function testCheckLoginFalsoSemSessao(): void
    {
        $this->assertFalse($this->service->checkLogin());
    }

    public function testCheckLoginVerdadeiroParaUsuarioAtivoNaSessao(): void
    {
        $user = $this->repositorio->createUser(UserFactory::comum('ana@exemplo.com'));
        $_SESSION['user_id'] = $user->getId();

        $this->assertTrue($this->service->checkLogin());
    }

    public function testCheckLoginFalsoQuandoUsuarioDaSessaoFoiRemovido(): void
    {
        $user = $this->repositorio->createUser(UserFactory::comum('ana@exemplo.com'));
        $_SESSION['user_id'] = $user->getId();
        $this->repositorio->deleteUser($user->getId());

        $this->assertFalse($this->service->checkLogin());
    }

    public function testCheckLoginFalsoQuandoUsuarioDaSessaoFoiDesativado(): void
    {
        $user = $this->repositorio->createUser(UserFactory::inativo('ana@exemplo.com'));
        $_SESSION['user_id'] = $user->getId();

        $this->assertFalse($this->service->checkLogin());
    }

    public function testCheckAdminSoParaPerfilAdmin(): void
    {
        $this->assertFalse($this->service->checkAdmin());

        $_SESSION['usuario_role'] = 1;
        $this->assertFalse($this->service->checkAdmin());

        $_SESSION['usuario_role'] = 2;
        $this->assertTrue($this->service->checkAdmin());
    }

    private function mensagemDe(string $email, string $senha): string
    {
        try {
            $this->service->auth($email, $senha);
        } catch (UsuarioException $e) {
            return $e->getMessage();
        }

        $this->fail('Era esperada UsuarioException.');
    }
}
