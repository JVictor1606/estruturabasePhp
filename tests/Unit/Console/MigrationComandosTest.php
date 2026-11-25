<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Src\Console\Comando\MigrationMarcarComando;
use Src\Console\Comando\MigrationNovoComando;
use Src\Console\Comando\MigrationStatusComando;
use Src\Console\Migracoes\CatalogoDeMigracoes;
use Src\Console\Migracoes\GeradorDeMigracao;
use Tests\Support\RegistroEmMemoria;
use Tests\Support\SaidaEmMemoria;
use Tests\Support\UsaPastaTemporaria;

#[CoversClass(MigrationStatusComando::class)]
#[CoversClass(MigrationNovoComando::class)]
#[CoversClass(MigrationMarcarComando::class)]
final class MigrationComandosTest extends TestCase
{
    use UsaPastaTemporaria;

    private SaidaEmMemoria $saida;

    protected function setUp(): void
    {
        $this->criarPasta();
        $this->saida = new SaidaEmMemoria();
    }

    protected function tearDown(): void
    {
        $this->apagarPasta();
    }

    private function comandoStatus(RegistroEmMemoria $registro): MigrationStatusComando
    {
        return new MigrationStatusComando(new CatalogoDeMigracoes($this->pasta), static fn (): RegistroEmMemoria => $registro, $this->saida);
    }

    private function comandoMarcar(RegistroEmMemoria $registro): MigrationMarcarComando
    {
        return new MigrationMarcarComando(new CatalogoDeMigracoes($this->pasta), static fn (): RegistroEmMemoria => $registro, $this->saida);
    }

    private function comandoNovo(): MigrationNovoComando
    {
        $gerador = new GeradorDeMigracao($this->pasta, static fn (): DateTimeImmutable => new DateTimeImmutable('2026-11-25 10:35:00'));

        return new MigrationNovoComando($gerador, $this->saida);
    }

    // ---------- status ----------

    public function testStatusMostraAplicadasEPendentesEFalhaSeHouverPendente(): void
    {
        $this->escrever('Exemplo/20261120_090000_a.sql', 'ALTER TABLE a ADD x int;');
        $this->escrever('Exemplo/20261125_100000_b.sql', 'ALTER TABLE a ADD y int;');
        $registro = new RegistroEmMemoria([
            'Exemplo/20261120_090000_a.sql' => ['checksum' => CatalogoDeMigracoes::checksum('ALTER TABLE a ADD x int;'), 'executada_em' => '2026-11-20 09:10:00'],
        ]);

        $codigo = $this->comandoStatus($registro)->executar([], []);
        $texto = $this->saida->texto();

        $this->assertSame(1, $codigo);
        $this->assertStringContainsString('registro_em_memoria', $texto);
        $this->assertMatchesRegularExpression('/\[OK\].*20261120_090000_a\.sql.*2026-11-20 09:10:00/', $texto);
        $this->assertMatchesRegularExpression('/\[FALTA\].*20261125_100000_b\.sql/', $texto);
        $this->assertStringContainsString('1 aplicada', $texto);
        $this->assertStringContainsString('1 pendente', $texto);
    }

    public function testStatusComTudoEmDiaSaiComZero(): void
    {
        $this->escrever('Exemplo/20261120_090000_a.sql', 'ALTER TABLE a ADD x int;');
        $registro = new RegistroEmMemoria([
            'Exemplo/20261120_090000_a.sql' => ['checksum' => CatalogoDeMigracoes::checksum('ALTER TABLE a ADD x int;'), 'executada_em' => '2026-11-20 09:10:00'],
        ]);

        $this->assertSame(0, $this->comandoStatus($registro)->executar([], []));
    }

    public function testStatusAvisaDeMigrationEditadaDepoisDeAplicada(): void
    {
        $this->escrever('Exemplo/20261120_090000_a.sql', 'ALTER TABLE a ADD x int, ADD z int;');
        $registro = new RegistroEmMemoria([
            'Exemplo/20261120_090000_a.sql' => ['checksum' => CatalogoDeMigracoes::checksum('ALTER TABLE a ADD x int;'), 'executada_em' => '2026-11-20 09:10:00'],
        ]);

        $codigo = $this->comandoStatus($registro)->executar([], []);

        $this->assertSame(1, $codigo);
        $this->assertMatchesRegularExpression('/\[ALTERADA\].*20261120_090000_a\.sql/', $this->saida->texto());
    }

    public function testStatusAvisaDeArquivoVazioEDeRegistroSemArquivo(): void
    {
        $this->escrever('Exemplo/001_vazia.sql', '');
        $registro = new RegistroEmMemoria(['Exemplo/sumiu.sql' => ['checksum' => 'x', 'executada_em' => '2026-11-01 08:00:00']]);

        $this->comandoStatus($registro)->executar([], []);
        $texto = $this->saida->texto();

        $this->assertMatchesRegularExpression('/\[FALTA\].*001_vazia\.sql.*vazio/i', $texto);
        $this->assertMatchesRegularExpression('/\[SUMIU\].*sumiu\.sql/', $texto);
    }

    public function testStatusSemNenhumaMigrationDizIssoEmVezDeFicarMudo(): void
    {
        $codigo = $this->comandoStatus(new RegistroEmMemoria())->executar([], []);

        $this->assertSame(0, $codigo);
        $this->assertStringContainsString('Nenhuma migration', $this->saida->texto());
    }

    // ---------- novo ----------

    public function testNovoCriaOArquivoEMostraOCaminho(): void
    {
        $codigo = $this->comandoNovo()->executar(['Exemplo', 'novos_campos'], []);

        $this->assertSame(0, $codigo);
        $this->assertFileExists($this->pasta . '/Exemplo/20261125_103500_novos_campos.sql');
        $this->assertStringContainsString('Exemplo/20261125_103500_novos_campos.sql', $this->saida->texto());
    }

    public function testNovoSemOsDoisArgumentosExplicaOUso(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('migration:novo');

        $this->comandoNovo()->executar(['Exemplo'], []);
    }

    // ---------- marcar ----------

    public function testMarcarSemConfirmacaoNaoGravaNada(): void
    {
        $this->escrever('Exemplo/20261120_090000_a.sql', 'ALTER TABLE a ADD x int;');
        $registro = new RegistroEmMemoria();

        $codigo = $this->comandoMarcar($registro)->executar(['Exemplo/20261120_090000_a.sql'], []);

        $this->assertSame(1, $codigo);
        $this->assertSame([], $registro->aplicadas());
        $this->assertStringContainsString('--sim', $this->saida->texto());
        $this->assertStringContainsString('registro_em_memoria', $this->saida->texto());
    }

    public function testMarcarComConfirmacaoRegistraComOChecksumAtual(): void
    {
        $this->escrever('Exemplo/20261120_090000_a.sql', 'ALTER TABLE a ADD x int;');
        $registro = new RegistroEmMemoria();

        $codigo = $this->comandoMarcar($registro)->executar(['Exemplo/20261120_090000_a.sql'], ['sim' => true]);

        $this->assertSame(0, $codigo);
        $this->assertSame(
            CatalogoDeMigracoes::checksum('ALTER TABLE a ADD x int;'),
            $registro->aplicadas()['Exemplo/20261120_090000_a.sql']['checksum']
        );
    }

    public function testMarcarRecusaArquivoQueNaoExiste(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->comandoMarcar(new RegistroEmMemoria())->executar(['Exemplo/nao-existe.sql'], ['sim' => true]);
    }

    public function testMarcarRecusaArquivoVazio(): void
    {
        $this->escrever('Exemplo/001_vazia.sql', "-- so comentario\n");
        $registro = new RegistroEmMemoria();

        $this->expectException(InvalidArgumentException::class);
        try {
            $this->comandoMarcar($registro)->executar(['Exemplo/001_vazia.sql'], ['sim' => true]);
        } finally {
            $this->assertSame([], $registro->aplicadas());
        }
    }

    public function testMarcarNaoRegistraDuasVezes(): void
    {
        $this->escrever('Exemplo/20261120_090000_a.sql', 'ALTER TABLE a ADD x int;');
        $registro = new RegistroEmMemoria(['Exemplo/20261120_090000_a.sql' => ['checksum' => 'x', 'executada_em' => '2026-11-20 09:10:00']]);

        $codigo = $this->comandoMarcar($registro)->executar(['Exemplo/20261120_090000_a.sql'], ['sim' => true]);

        $this->assertSame(0, $codigo);
        $this->assertStringContainsString('já está registrada', $this->saida->texto());
        $this->assertSame('x', $registro->aplicadas()['Exemplo/20261120_090000_a.sql']['checksum']);
    }

    public function testMarcarTodasRegistraSomenteAsPendentesComSql(): void
    {
        $this->escrever('Exemplo/20261120_090000_a.sql', 'ALTER TABLE a ADD x int;');
        $this->escrever('Exemplo/20261125_100000_b.sql', 'ALTER TABLE a ADD y int;');
        $this->escrever('Exemplo/001_vazia.sql', '');
        $registro = new RegistroEmMemoria();

        $this->comandoMarcar($registro)->executar([], ['todas' => true, 'sim' => true]);

        $this->assertEqualsCanonicalizing(
            ['Exemplo/20261120_090000_a.sql', 'Exemplo/20261125_100000_b.sql'],
            array_keys($registro->aplicadas())
        );
    }
}
