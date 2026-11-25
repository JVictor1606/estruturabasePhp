<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Migracoes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Src\Console\Migracoes\CatalogoDeMigracoes;
use Src\Console\Migracoes\Migracao;
use Tests\Support\UsaPastaTemporaria;

#[CoversClass(CatalogoDeMigracoes::class)]
#[CoversClass(Migracao::class)]
final class CatalogoDeMigracoesTest extends TestCase
{
    use UsaPastaTemporaria;

    protected function setUp(): void
    {
        $this->criarPasta();
    }

    protected function tearDown(): void
    {
        $this->apagarPasta();
    }

    private function catalogo(): CatalogoDeMigracoes
    {
        return new CatalogoDeMigracoes($this->pasta);
    }

    public function testListaEmOrdemGlobalPeloNomeDoArquivoEntrePastas(): void
    {
        $this->escrever('Exemplo/20261125_100000_b.sql', 'ALTER TABLE a ADD x int;');
        $this->escrever('Usuarios/20261120_090000_a.sql', 'ALTER TABLE b ADD y int;');
        $this->escrever('Exemplo/001_antiga.sql', 'ALTER TABLE c ADD z int;');

        $caminhos = array_map(static fn (Migracao $m): string => $m->caminho, $this->catalogo()->todas());

        $this->assertSame(
            ['Exemplo/001_antiga.sql', 'Usuarios/20261120_090000_a.sql', 'Exemplo/20261125_100000_b.sql'],
            $caminhos
        );
    }

    public function testIgnoraArquivosQueNaoSaoSql(): void
    {
        $this->escrever('Exemplo/20261125_100000_a.sql', 'SELECT 1;');
        $this->escrever('Exemplo/LEIAME.md', 'texto');
        $this->escrever('Exemplo/20261125_100000_a.sql.bak', 'SELECT 1;');

        $this->assertCount(1, $this->catalogo()->todas());
    }

    public function testCaminhoRelativoSempreUsaBarraNormal(): void
    {
        $this->escrever('Exemplo/20261125_100000_a.sql', 'SELECT 1;');

        $this->assertSame('Exemplo/20261125_100000_a.sql', $this->catalogo()->todas()[0]->caminho);
    }

    public function testChecksumNaoDependeDeFimDeLinhaWindowsOuLinux(): void
    {
        $this->assertSame(
            CatalogoDeMigracoes::checksum("ALTER TABLE a\r\n  ADD x int;\r\n"),
            CatalogoDeMigracoes::checksum("ALTER TABLE a\n  ADD x int;\n")
        );
    }

    public function testChecksumMudaQuandoOSqlMuda(): void
    {
        $this->assertNotSame(
            CatalogoDeMigracoes::checksum('ALTER TABLE a ADD x int;'),
            CatalogoDeMigracoes::checksum('ALTER TABLE a ADD y int;')
        );
    }

    public function testArquivoSoComComentariosEhVazio(): void
    {
        $this->escrever('Exemplo/20261125_100000_a.sql', "-- so comentario\n# outro\n/* bloco\n varias linhas */\n");

        $this->assertTrue($this->catalogo()->todas()[0]->vazia);
    }

    public function testArquivoTotalmenteEmBrancoEhVazio(): void
    {
        $this->escrever('Exemplo/20261125_100000_a.sql', '');

        $this->assertTrue($this->catalogo()->todas()[0]->vazia);
    }

    public function testArquivoComSqlNaoEhVazio(): void
    {
        $this->escrever('Exemplo/20261125_100000_a.sql', "-- cabecalho\nALTER TABLE a ADD x int;\n");

        $this->assertFalse($this->catalogo()->todas()[0]->vazia);
    }

    public function testPastaInexistenteDevolveListaVazia(): void
    {
        $this->assertSame([], (new CatalogoDeMigracoes($this->pasta . '/nao-existe'))->todas());
    }
}
