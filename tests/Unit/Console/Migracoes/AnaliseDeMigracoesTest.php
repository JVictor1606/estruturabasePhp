<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Migracoes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Src\Console\Migracoes\AnaliseDeMigracoes;
use Src\Console\Migracoes\ItemDeMigracao;
use Src\Console\Migracoes\Migracao;

#[CoversClass(AnaliseDeMigracoes::class)]
#[CoversClass(ItemDeMigracao::class)]
#[CoversClass(Migracao::class)]
final class AnaliseDeMigracoesTest extends TestCase
{
    private function arquivo(string $caminho, string $checksum, bool $vazia = false): Migracao
    {
        return new Migracao($caminho, $checksum, $vazia);
    }

    public function testArquivoRegistradoComMesmoChecksumEstaAplicado(): void
    {
        $itens = AnaliseDeMigracoes::analisar(
            [$this->arquivo('Exemplo/a.sql', 'h1')],
            ['Exemplo/a.sql' => ['checksum' => 'h1', 'executada_em' => '2026-11-20 09:00:00']]
        );

        $this->assertSame(AnaliseDeMigracoes::APLICADA, $itens[0]->situacao);
        $this->assertSame('2026-11-20 09:00:00', $itens[0]->executadaEm);
    }

    public function testArquivoSemRegistroEstaPendente(): void
    {
        $itens = AnaliseDeMigracoes::analisar([$this->arquivo('Exemplo/a.sql', 'h1')], []);

        $this->assertSame(AnaliseDeMigracoes::PENDENTE, $itens[0]->situacao);
        $this->assertNull($itens[0]->executadaEm);
    }

    public function testArquivoEditadoDepoisDeAplicadoFicaAlterado(): void
    {
        $itens = AnaliseDeMigracoes::analisar(
            [$this->arquivo('Exemplo/a.sql', 'h2')],
            ['Exemplo/a.sql' => ['checksum' => 'h1', 'executada_em' => '2026-11-20 09:00:00']]
        );

        $this->assertSame(AnaliseDeMigracoes::ALTERADA, $itens[0]->situacao);
    }

    public function testRegistroSemArquivoFicaNoFinalComoSumiu(): void
    {
        $itens = AnaliseDeMigracoes::analisar(
            [$this->arquivo('Exemplo/a.sql', 'h1')],
            [
                'Exemplo/a.sql' => ['checksum' => 'h1', 'executada_em' => '2026-11-20 09:00:00'],
                'Exemplo/sumiu.sql' => ['checksum' => 'x', 'executada_em' => '2026-11-21 09:00:00'],
            ]
        );

        $this->assertCount(2, $itens);
        $this->assertSame('Exemplo/sumiu.sql', $itens[1]->caminho);
        $this->assertSame(AnaliseDeMigracoes::SEM_ARQUIVO, $itens[1]->situacao);
    }

    public function testMantemAOrdemDoCatalogo(): void
    {
        $itens = AnaliseDeMigracoes::analisar(
            [$this->arquivo('A/1.sql', 'a'), $this->arquivo('B/2.sql', 'b'), $this->arquivo('A/3.sql', 'c')],
            []
        );

        $this->assertSame(['A/1.sql', 'B/2.sql', 'A/3.sql'], array_map(static fn (ItemDeMigracao $i): string => $i->caminho, $itens));
    }

    public function testPropagaAMarcaDeArquivoVazio(): void
    {
        $itens = AnaliseDeMigracoes::analisar([$this->arquivo('Exemplo/a.sql', 'h1', true)], []);

        $this->assertTrue($itens[0]->vazia);
    }

    public function testTudoAplicadoEConsistenteNaoTemProblema(): void
    {
        $itens = AnaliseDeMigracoes::analisar(
            [$this->arquivo('Exemplo/a.sql', 'h1')],
            ['Exemplo/a.sql' => ['checksum' => 'h1', 'executada_em' => '2026-11-20 09:00:00']]
        );

        $this->assertFalse(AnaliseDeMigracoes::temProblema($itens));
    }

    public function testPendenteAlteradoOuSumidoContamComoProblema(): void
    {
        $pendente = AnaliseDeMigracoes::analisar([$this->arquivo('a.sql', 'h')], []);
        $alterado = AnaliseDeMigracoes::analisar([$this->arquivo('a.sql', 'novo')], ['a.sql' => ['checksum' => 'velho', 'executada_em' => 'x']]);
        $sumido = AnaliseDeMigracoes::analisar([], ['a.sql' => ['checksum' => 'h', 'executada_em' => 'x']]);

        $this->assertTrue(AnaliseDeMigracoes::temProblema($pendente));
        $this->assertTrue(AnaliseDeMigracoes::temProblema($alterado));
        $this->assertTrue(AnaliseDeMigracoes::temProblema($sumido));
    }
}
