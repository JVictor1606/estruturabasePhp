<?php

declare(strict_types=1);

namespace Tests\Unit\App\Http;

use Src\Core\Request;
use Src\Core\Validations;
use Src\App\Http\Requests\LoginRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Support\Rede;

#[CoversClass(LoginRequest::class)]
#[CoversClass(Request::class)]
#[CoversClass(Validations::class)]
final class LoginRequestTest extends TestCase
{
    public function testCamposVaziosFalhamComErroNosDoisCampos(): void
    {
        $request = new LoginRequest(['email' => '', 'senha' => '']);

        $this->assertTrue($request->fails());
        $this->assertArrayHasKey('email', $request->errors());
        $this->assertArrayHasKey('senha', $request->errors());
    }

    public function testEmailMalFormadoFalha(): void
    {
        $request = new LoginRequest(['email' => 'email-invalido', 'senha' => 'Senha@123']);

        $this->assertTrue($request->fails());
        $this->assertArrayHasKey('email', $request->errors());
        $this->assertArrayNotHasKey('senha', $request->errors());
    }

    public function testSenhaCurtaFalha(): void
    {
        $request = new LoginRequest(['email' => 'email-invalido', 'senha' => '123']);

        $this->assertArrayHasKey('senha', $request->errors());
    }

    public function testCamposAusentesFalham(): void
    {
        $request = new LoginRequest([]);

        $this->assertTrue($request->fails());
        $this->assertArrayHasKey('email', $request->errors());
        $this->assertArrayHasKey('senha', $request->errors());
    }

    public function testEspacosNasPontasSaoRemovidosAntesDeValidar(): void
    {
        $request = new LoginRequest(['email' => '   ', 'senha' => '   ']);

        $this->assertArrayHasKey('email', $request->errors());
        $this->assertArrayHasKey('senha', $request->errors());
    }

    #[Group('rede')]
    public function testPayloadValidoPassaENormalizaOEmail(): void
    {
        if (! Rede::dnsDisponivel()) {
            $this->markTestSkipped('Sem DNS: Validations::email consulta o MX do domínio.');
        }

        $request = new LoginRequest(['email' => '  Ana@Gmail.com ', 'senha' => 'Senha@123']);

        $this->assertFalse($request->fails());
        $this->assertSame(['email' => 'ana@gmail.com', 'senha' => 'Senha@123'], $request->validated());
    }

    #[Group('rede')]
    public function testEmailDeDominioSemMxFalha(): void
    {
        if (! Rede::dnsDisponivel()) {
            $this->markTestSkipped('Sem DNS: Validations::email consulta o MX do domínio.');
        }

        $request = new LoginRequest(['email' => 'ana@dominio-que-nao-existe-xyz.invalid', 'senha' => 'Senha@123']);

        $this->assertArrayHasKey('email', $request->errors());
    }
}
