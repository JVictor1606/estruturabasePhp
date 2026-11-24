<?php

declare(strict_types=1);

namespace Tests\Unit\App\Services;

use Src\Core\Auth;
use Src\App\Services\RecuperacaoSenhaService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeEmailSender;
use Tests\Support\FakeUserRepository;
use Tests\Support\UserFactory;

#[CoversClass(RecuperacaoSenhaService::class)]
#[CoversClass(Auth::class)]
#[Group('seguranca')]
final class RecuperacaoSenhaServiceTest extends TestCase
{
    private FakeEmailSender $email;
    private RecuperacaoSenhaService $service;

    protected function setUp(): void
    {
        $_SESSION = [];
        $repositorio = new FakeUserRepository();
        $repositorio->createUser(UserFactory::comum('ana@exemplo.com'));

        $this->email = new FakeEmailSender();
        $this->service = new RecuperacaoSenhaService($repositorio, $this->email);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testEnviaCodigoDeSeisDigitosParaUsuarioCadastrado(): void
    {
        $this->service->solicitarCodigoRecuperacao('ana@exemplo.com');

        $this->assertCount(1, $this->email->enviados);
        $this->assertSame('ana@exemplo.com', $this->email->enviados[0]['para']);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->email->ultimoCodigo());
    }

    public function testCodigoCorretoEAceito(): void
    {
        $this->service->solicitarCodigoRecuperacao('ana@exemplo.com');

        $this->assertTrue($this->service->verificarCodigo('ana@exemplo.com', $this->email->ultimoCodigo()));
    }

    public function testCodigoErradoERecusado(): void
    {
        $this->service->solicitarCodigoRecuperacao('ana@exemplo.com');
        $errado = $this->email->ultimoCodigo() === '123456' ? '654321' : '123456';

        $this->assertFalse($this->service->verificarCodigo('ana@exemplo.com', $errado));
    }

    public function testCodigoNaoServeParaOutroEmail(): void
    {
        $this->service->solicitarCodigoRecuperacao('ana@exemplo.com');

        $this->assertFalse($this->service->verificarCodigo('outra@exemplo.com', $this->email->ultimoCodigo()));
    }

    public function testCodigoEInvalidadoDepoisDeCincoErrosMesmoQueODigiteCertoEmSeguida(): void
    {
        $this->service->solicitarCodigoRecuperacao('ana@exemplo.com');
        $certo = $this->email->ultimoCodigo();
        $errado = $certo === '111111' ? '222222' : '111111';

        for ($tentativa = 1; $tentativa <= 5; $tentativa++) {
            $this->assertFalse($this->service->verificarCodigo('ana@exemplo.com', $errado));
        }

        $this->assertFalse(
            $this->service->verificarCodigo('ana@exemplo.com', $certo),
            'Seis dígitos sem limite de tentativas podem ser adivinhados: após 5 erros o código precisa morrer.'
        );
        $this->assertTrue(Auth::codigoExpirado(), 'O usuário precisa pedir um código novo.');
    }

    public function testQuatroErrosAindaPermitemAcertar(): void
    {
        $this->service->solicitarCodigoRecuperacao('ana@exemplo.com');
        $certo = $this->email->ultimoCodigo();
        $errado = $certo === '111111' ? '222222' : '111111';

        for ($tentativa = 1; $tentativa <= 4; $tentativa++) {
            $this->service->verificarCodigo('ana@exemplo.com', $errado);
        }

        $this->assertTrue($this->service->verificarCodigo('ana@exemplo.com', $certo));
    }

    public function testEmailInexistenteNaoLancaErroNemEnviaEmail(): void
    {
        $this->service->solicitarCodigoRecuperacao('ninguem@exemplo.com');

        $this->assertSame([], $this->email->enviados);
    }

    public function testEmailInexistenteDeixaOMesmoRastroNaSessaoQueUmExistente(): void
    {
        $this->service->solicitarCodigoRecuperacao('ana@exemplo.com');
        $chavesDoExistente = array_keys($_SESSION);

        $_SESSION = [];
        $this->service->solicitarCodigoRecuperacao('ninguem@exemplo.com');
        $chavesDoInexistente = array_keys($_SESSION);

        $this->assertSame($chavesDoExistente, $chavesDoInexistente, 'O fluxo seguinte não pode denunciar se o e-mail existe.');
        $this->assertFalse(Auth::codigoExpirado(), 'Para quem tenta, parece que um código foi enviado.');
    }

    public function testEmailInexistenteNuncaTemCodigoValido(): void
    {
        $this->service->solicitarCodigoRecuperacao('ninguem@exemplo.com');

        foreach (['000000', '123456', '999999'] as $palpite) {
            $this->assertFalse($this->service->verificarCodigo('ninguem@exemplo.com', $palpite));
        }
    }
}
