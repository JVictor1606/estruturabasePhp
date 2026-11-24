<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Usuarios\Services;

use Modular\Modules\Usuarios\Domain\UsuarioException;
use Modular\Modules\Usuarios\Services\UserService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeUserRepository;
use Tests\Support\UserFactory;

#[CoversClass(UserService::class)]
#[CoversClass(UsuarioException::class)]
#[Group('seguranca')]
final class UserServiceTest extends TestCase
{
    public function testAdminNaoPodeDeletarAPropriaConta(): void
    {
        $repositorio = new FakeUserRepository();
        $admin = $repositorio->createUser(UserFactory::admin('adm@exemplo.com'));
        $service = new UserService($repositorio);

        try {
            $service->deleteUser($admin->getId(), $admin->getId());
            $this->fail('Era esperada UsuarioException.');
        } catch (UsuarioException $e) {
            $this->assertSame('Você não pode deletar a sua própria conta.', $e->getMessage());
            $this->assertSame(422, $e->getStatusCode());
        }

        $this->assertNotNull($repositorio->getUserById($admin->getId()), 'A conta não pode ter sido removida.');
    }
}
