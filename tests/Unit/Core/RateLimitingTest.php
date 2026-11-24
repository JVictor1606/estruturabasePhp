<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Modular\Core\RateLimiting;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(RateLimiting::class)]
#[Group('seguranca')]
final class RateLimitingTest extends TestCase
{
    private string $arquivo;
    private int $agora = 1_000_000;
    private RateLimiting $limitador;

    protected function setUp(): void
    {
        $this->arquivo = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rate-limit-teste-' . uniqid('', true) . '.json';
        $this->limitador = $this->novoLimitador();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->arquivo)) {
            unlink($this->arquivo);
        }
    }

    public function testChaveLivreNaoTemBloqueio(): void
    {
        $this->assertSame(0, $this->limitador->segundosRestantes('login#1.1.1.1#ana@x.com'));
    }

    public function testQuatroFalhasNaoBloqueiam(): void
    {
        $this->falhar('k', 4);

        $this->assertSame(0, $this->limitador->segundosRestantes('k'));
    }

    public function testQuintaFalhaBloqueiaPorUmMinuto(): void
    {
        $this->falhar('k', 5);

        $this->assertSame(60, $this->limitador->segundosRestantes('k'));
    }

    public function testBloqueioDiminuiComOPassarDoTempo(): void
    {
        $this->falhar('k', 5);
        $this->agora += 45;

        $this->assertSame(15, $this->limitador->segundosRestantes('k'));
    }

    public function testBloqueioExpiraMasAProximaFalhaBloqueiaPorMaisTempo(): void
    {
        $this->falhar('k', 5);
        $this->agora += 61;
        $this->assertSame(0, $this->limitador->segundosRestantes('k'), 'O bloqueio de 1 minuto deveria ter acabado.');

        $this->limitador->registrarFalha('k');
        $this->assertSame(300, $this->limitador->segundosRestantes('k'), 'Errar de novo deveria bloquear por 5 minutos.');

        $this->agora += 301;
        $this->limitador->registrarFalha('k');
        $this->assertSame(900, $this->limitador->segundosRestantes('k'), 'E depois por 15 minutos.');
    }

    public function testBloqueioNaoPassaDoTeto(): void
    {
        $this->falhar('k', 5);
        foreach ([300, 900, 1800, 3600, 3600] as $esperado) {
            $this->agora += $this->limitador->segundosRestantes('k') + 1;
            $this->limitador->registrarFalha('k');
            $this->assertSame($esperado, $this->limitador->segundosRestantes('k'));
        }
    }

    public function testSucessoZeraOContador(): void
    {
        $this->falhar('k', 4);
        $this->limitador->limpar('k');
        $this->falhar('k', 4);

        $this->assertSame(0, $this->limitador->segundosRestantes('k'), 'Após limpar, 4 novas falhas não deveriam bloquear.');
    }

    public function testEsqueceDepoisDeUmTempoSemFalhas(): void
    {
        $this->falhar('k', 4);
        $this->agora += 3600;
        $this->limitador->registrarFalha('k');

        $this->assertSame(0, $this->limitador->segundosRestantes('k'), 'Falhas antigas não deveriam somar com a nova.');
    }

    public function testChavesSaoIndependentes(): void
    {
        $this->falhar('a', 5);

        $this->assertSame(60, $this->limitador->segundosRestantes('a'));
        $this->assertSame(0, $this->limitador->segundosRestantes('b'));
    }

    public function testEstadoPersisteEntreInstancias(): void
    {
        $this->falhar('k', 5);

        $this->assertSame(60, $this->novoLimitador()->segundosRestantes('k'));
    }

    public function testArquivoCorrompidoEhTratadoComoVazio(): void
    {
        file_put_contents($this->arquivo, '{isso não é json');

        $this->assertSame(0, $this->limitador->segundosRestantes('k'));
        $this->limitador->registrarFalha('k');
        $this->assertSame(0, $this->limitador->segundosRestantes('k'));
    }

    public function testChaveNormalizaEmailEIdentificaOEscopo(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.7';

        $this->assertSame('login#10.0.0.7#ana@exemplo.com', RateLimiting::chave('login', '  ANA@Exemplo.com '));
        $this->assertNotSame(RateLimiting::chave('login', 'ana@exemplo.com'), RateLimiting::chave('recuperacao', 'ana@exemplo.com'));
    }

    public function testMensagemFalaEmSegundosOuMinutos(): void
    {
        $this->assertSame('Muitas tentativas. Tente novamente em 45 segundos.', RateLimiting::mensagem(45));
        $this->assertSame('Muitas tentativas. Tente novamente em 1 minuto.', RateLimiting::mensagem(60));
        $this->assertSame('Muitas tentativas. Tente novamente em 5 minutos.', RateLimiting::mensagem(300));
        $this->assertSame('Muitas tentativas. Tente novamente em 2 minutos.', RateLimiting::mensagem(61));
    }

    private function novoLimitador(): RateLimiting
    {
        return new RateLimiting($this->arquivo, fn (): int => $this->agora);
    }

    private function falhar(string $chave, int $vezes): void
    {
        for ($i = 0; $i < $vezes; $i++) {
            $this->limitador->registrarFalha($chave);
        }
    }
}
