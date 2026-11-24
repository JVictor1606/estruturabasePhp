<?php

declare(strict_types=1);

namespace Tests\Unit\App\Domain;

use Src\App\Enums\Perfil;
use Src\App\ValueObjects\Senha;
use Src\App\Enums\Status;
use Src\App\Models\User;
use Src\App\Http\Exceptions\Usuario\UsuarioException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\UserFactory;

#[CoversClass(User::class)]
#[CoversClass(Perfil::class)]
#[CoversClass(Status::class)]
#[CoversClass(UsuarioException::class)]
final class UserTest extends TestCase
{
    public function testPerfilEStatusTemPadraoSeguro(): void
    {
        $user = new User('Ana', 'ana@exemplo.com', Senha::senha('Senha@123'), null, null);

        $this->assertSame(Perfil::USER, $user->getPerfil());
        $this->assertSame(Status::ATIVO, $user->getStatus());
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->isAtivo());
        $this->assertSame(1, $user->getVersion());
    }

    public function testGeraIdUuidUnicoQuandoNaoInformado(): void
    {
        $a = UserFactory::comum('a@exemplo.com');
        $b = UserFactory::comum('b@exemplo.com');

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $a->getId());
        $this->assertNotSame($a->getId(), $b->getId());
    }

    public function testVerificaSenhaDelegaParaOValueObject(): void
    {
        $user = UserFactory::comum('ana@exemplo.com', 'Senha@123');

        $this->assertTrue($user->verificaSenha('Senha@123'));
        $this->assertFalse($user->verificaSenha('outra'));
    }

    public function testUsuarioInativoNaoEstaAtivo(): void
    {
        $this->assertFalse(UserFactory::inativo('ana@exemplo.com')->isAtivo());
    }

    public function testAdminEReconhecido(): void
    {
        $this->assertTrue(UserFactory::admin('adm@exemplo.com')->isAdmin());
    }

    public function testAtualizaSenhaTrocaOHash(): void
    {
        $user = UserFactory::comum('ana@exemplo.com', 'Senha@123');

        $user->atualizaSenha(Senha::senha('Nova@1234'));

        $this->assertFalse($user->verificaSenha('Senha@123'));
        $this->assertTrue($user->verificaSenha('Nova@1234'));
    }

    public function testRecusaEmailInvalido(): void
    {
        $user = UserFactory::comum('ana@exemplo.com');

        $this->expectException(UsuarioException::class);
        $user->setEmail('isso-nao-e-email');
    }

    public function testRemoveAspasEMarcadoresHtmlDoNome(): void
    {
        $user = UserFactory::comum('ana@exemplo.com');

        $user->setNome("Ana <b>\"O'Neil\"</b>");

        $this->assertSame('Ana bONeil/b', $user->getNome());
    }

    public function testFromArrayTransformaSenhaPuraEmHash(): void
    {
        $user = User::fromArray([
            'nome' => 'Ana',
            'email' => 'ana@exemplo.com',
            'senha' => 'Senha@123',
            'perfil' => 2,
            'status' => 0,
        ]);

        $this->assertTrue($user->verificaSenha('Senha@123'));
        $this->assertNotSame('Senha@123', $user->getSenha()->hash());
        $this->assertSame(Perfil::ADMIN, $user->getPerfil());
        $this->assertSame(Status::INATIVO, $user->getStatus());
    }

    public function testFromArrayAceitaSenhaJaCriptografada(): void
    {
        $senha = Senha::senha('Senha@123');

        $user = User::fromArray(['nome' => 'Ana', 'email' => 'ana@exemplo.com', 'senha' => $senha]);

        $this->assertSame($senha, $user->getSenha());
    }

    public function testPerfilInvalidoLancaExcecaoDeDominio(): void
    {
        $this->expectException(UsuarioException::class);
        Perfil::validar(99);
    }

    public function testStatusInvalidoLancaExcecaoDeDominio(): void
    {
        $this->expectException(UsuarioException::class);
        Status::validar(7);
    }
}
