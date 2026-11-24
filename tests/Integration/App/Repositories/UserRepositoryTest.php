<?php

declare(strict_types=1);

namespace Tests\Integration\App\Repositories;

use Src\Core\Database;
use Src\App\Enums\Perfil;
use Src\App\Enums\Status;
use Src\App\Http\Exceptions\Usuario\UsuarioException;
use Src\App\Infrastructure\Repositories\UserRepository;
use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Support\DatabaseTestCase;
use Tests\Support\UserFactory;

#[CoversClass(UserRepository::class)]
#[CoversClass(Database::class)]
final class UserRepositoryTest extends DatabaseTestCase
{
    public function testSalvaEBuscaPorEmailPreservandoOsDados(): void
    {
        $original = UserFactory::admin('ana@exemplo.com', 'Senha@123');
        $this->repositorio()->createUser($original);

        $lido = $this->repositorio()->getUserByEmail('ana@exemplo.com');

        $this->assertNotNull($lido);
        $this->assertSame($original->getId(), $lido->getId());
        $this->assertSame('Usuario Teste', $lido->getNome());
        $this->assertSame(Perfil::ADMIN, $lido->getPerfil());
        $this->assertSame(Status::ATIVO, $lido->getStatus());
        $this->assertSame(1, $lido->getVersion());
        $this->assertTrue($lido->verificaSenha('Senha@123'));
    }

    public function testNaoGravaASenhaEmTextoPuro(): void
    {
        $this->repositorio()->createUser(UserFactory::comum('ana@exemplo.com', 'Senha@123'));

        $gravado = (string) self::$pdo->query("SELECT senha FROM tb_usuarios WHERE email = 'ana@exemplo.com'")->fetchColumn();

        $this->assertNotSame('Senha@123', $gravado);
        $this->assertTrue(password_verify('Senha@123', $gravado));
    }

    public function testBuscaPorId(): void
    {
        $user = $this->repositorio()->createUser(UserFactory::comum('ana@exemplo.com'));

        $this->assertSame('ana@exemplo.com', $this->repositorio()->getUserById($user->getId())?->getEmail());
    }

    public function testRetornaNullQuandoNaoEncontra(): void
    {
        $this->assertNull($this->repositorio()->getUserByEmail('ninguem@exemplo.com'));
        $this->assertNull($this->repositorio()->getUserById('00000000-0000-0000-0000-000000000000'));
    }

    public function testBancoImpedeEmailDuplicado(): void
    {
        $this->repositorio()->createUser(UserFactory::comum('ana@exemplo.com'));

        $this->expectException(PDOException::class);
        $this->repositorio()->createUser(UserFactory::comum('ana@exemplo.com'));
    }

    public function testAtualizaOsDadosEIncrementaAVersao(): void
    {
        $user = $this->repositorio()->createUser(UserFactory::comum('ana@exemplo.com'));
        $user->setNome('Ana Maria');
        $user->setStatus(Status::INATIVO);

        $this->repositorio()->updateUser($user);

        $lido = $this->repositorio()->getUserById($user->getId());
        $this->assertSame('Ana Maria', $lido?->getNome());
        $this->assertSame(Status::INATIVO, $lido?->getStatus());
        $this->assertSame(2, $lido?->getVersion());
    }

    public function testLockOtimistaRecusaEdicaoSobreDadoDesatualizado(): void
    {
        $criado = $this->repositorio()->createUser(UserFactory::comum('ana@exemplo.com'));

        $copiaA = $this->repositorio()->getUserById($criado->getId());
        $copiaB = $this->repositorio()->getUserById($criado->getId());
        $this->assertNotNull($copiaA);
        $this->assertNotNull($copiaB);

        $copiaA->setNome('Alterado por A');
        $this->repositorio()->updateUser($copiaA);

        $copiaB->setNome('Alterado por B');
        $this->expectException(UsuarioException::class);
        $this->expectExceptionMessage('alterado por outra pessoa');
        $this->repositorio()->updateUser($copiaB);
    }

    public function testRemoveUsuario(): void
    {
        $user = $this->repositorio()->createUser(UserFactory::comum('ana@exemplo.com'));

        $this->assertTrue($this->repositorio()->deleteUser($user->getId()));
        $this->assertNull($this->repositorio()->getUserById($user->getId()));
    }

    public function testRemoverInexistenteRetornaFalso(): void
    {
        $this->assertFalse($this->repositorio()->deleteUser('00000000-0000-0000-0000-000000000000'));
    }

    public function testContaEPagina(): void
    {
        foreach (['a', 'b', 'c', 'd', 'e'] as $letra) {
            $this->repositorio()->createUser(UserFactory::comum("{$letra}@exemplo.com"));
        }

        $this->assertSame(5, $this->repositorio()->countUsers());
        $this->assertCount(2, $this->repositorio()->getAllUsersPaginado(2, 0));
        $this->assertCount(2, $this->repositorio()->getAllUsersPaginado(2, 2));
        $this->assertCount(1, $this->repositorio()->getAllUsersPaginado(2, 4));
        $this->assertCount(5, $this->repositorio()->getAllUser());
    }
}
