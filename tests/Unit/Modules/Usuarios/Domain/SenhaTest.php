<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Usuarios\Domain;

use Modular\Modules\Usuarios\Domain\Senha;
use Modular\Modules\Usuarios\Domain\UsuarioException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Senha::class)]
#[CoversClass(UsuarioException::class)]
final class SenhaTest extends TestCase
{
    public function testGuardaApenasOHashENuncaASenhaPura(): void
    {
        $senha = Senha::senha('Senha@123');

        $this->assertNotSame('Senha@123', $senha->hash());
        $this->assertTrue(password_verify('Senha@123', $senha->hash()));
    }

    public function testConfereSenhaCorreta(): void
    {
        $this->assertTrue(Senha::senha('Senha@123')->confere('Senha@123'));
    }

    public function testRejeitaSenhaIncorreta(): void
    {
        $this->assertFalse(Senha::senha('Senha@123')->confere('Senha@124'));
    }

    public function testRejeitaSenhaEmBranco(): void
    {
        $this->assertFalse(Senha::senha('Senha@123')->confere(''));
    }

    public function testMesmaSenhaGeraHashesDiferentes(): void
    {
        $this->assertNotSame(Senha::senha('Senha@123')->hash(), Senha::senha('Senha@123')->hash());
    }

    public function testReconstroiAPartirDoHashSalvoNoBanco(): void
    {
        $hash = Senha::senha('Senha@123')->hash();

        $this->assertTrue(Senha::senhaHash($hash)->confere('Senha@123'));
    }

    #[DataProvider('senhasQueViolamUmaRegra')]
    public function testRecusaSenhaQueViolaAsRegras(string $senha, string $trechoDoErro): void
    {
        try {
            Senha::senha($senha);
            $this->fail('Era esperada UsuarioException.');
        } catch (UsuarioException $e) {
            $this->assertStringStartsWith('Senha inválida:', $e->getMessage());
            $this->assertStringContainsString($trechoDoErro, $e->getMessage());
            $this->assertSame(422, $e->getStatusCode());
        }
    }

    /** @return array<string, array{string, string}> */
    public static function senhasQueViolamUmaRegra(): array
    {
        return [
            'curta demais' => ['Ab1!', '8 caracteres'],
            'sem caractere especial' => ['Senha1234', 'caracter especial'],
            'sem maiúscula' => ['senha@123', 'maiúscula'],
            'sem minúscula' => ['SENHA@123', 'minúscula'],
            'sem número' => ['Senha@abc', 'número'],
        ];
    }

    public function testListaTodasAsRegrasVioladasDeUmaVez(): void
    {
        try {
            Senha::senha('');
            $this->fail('Era esperada UsuarioException.');
        } catch (UsuarioException $e) {
            foreach (['8 caracteres', 'caracter especial', 'maiúscula', 'minúscula', 'número'] as $regra) {
                $this->assertStringContainsString($regra, $e->getMessage());
            }
        }
    }
}
