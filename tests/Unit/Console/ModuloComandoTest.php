<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Src\Console\Comando\ModuloComando;
use Src\Console\Modulos\GeradorDeModulo;
use Tests\Support\SaidaEmMemoria;
use Tests\Support\UsaPastaTemporaria;

#[CoversClass(ModuloComando::class)]
final class ModuloComandoTest extends TestCase
{
    use UsaPastaTemporaria;

    private SaidaEmMemoria $saida;

    protected function setUp(): void
    {
        $this->criarPasta();
        $this->saida = new SaidaEmMemoria();

        foreach (GeradorDeModulo::ARQUIVOS_DO_MODELO as $arquivo) {
            $this->escrever($arquivo, (string) file_get_contents(dirname(__DIR__, 3) . '/' . $arquivo));
        }
    }

    protected function tearDown(): void
    {
        $this->apagarPasta();
    }

    private function comando(): ModuloComando
    {
        return new ModuloComando(
            new GeradorDeModulo($this->pasta, static fn (): DateTimeImmutable => new DateTimeImmutable('2026-11-25 10:35:00')),
            $this->saida
        );
    }

    public function testSemONomeExplicaOUso(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('modulo <Nome>');

        $this->comando()->executar([], []);
    }

    public function testCriaOModuloEListaOsArquivos(): void
    {
        $codigo = $this->comando()->executar(['Produto'], []);

        $this->assertSame(0, $codigo);
        $this->assertFileExists($this->pasta . '/src/App/Http/Controllers/ProdutoController.php');
        $this->assertStringContainsString('+ src/App/Http/Controllers/ProdutoController.php', $this->saida->texto());
    }

    public function testLembraDoMenuLateralQueEhUmPontoCompartilhado(): void
    {
        $this->comando()->executar(['Produto'], []);

        $this->assertStringContainsString('sidebar.php', $this->saida->texto());
    }

    public function testDryRunNaoCriaNada(): void
    {
        $antes = $this->arquivosCriados();

        $this->comando()->executar(['Produto'], ['dry-run' => true]);

        $this->assertSame($antes, $this->arquivosCriados());
        $this->assertStringContainsString('Simulação', $this->saida->texto());
    }

    public function testSemTabelaLembraDeCriarAMigrationEComTabelaNao(): void
    {
        $this->comando()->executar(['Produto'], []);
        $this->assertStringContainsString('migration:novo', $this->saida->texto());

        $outra = new SaidaEmMemoria();
        $this->saida = $outra;
        $this->comando()->executar(['Pedido'], ['com-tabela' => true]);
        $this->assertStringNotContainsString('migration:novo', $outra->texto());
        $this->assertFileExists($this->pasta . '/src/database/Migrations/Pedido/20261125_103500_criar_tabela_pedido.sql');
    }
}
