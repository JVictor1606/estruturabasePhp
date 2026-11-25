<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Src\Console\Comando\MariadbGerarComando;
use Src\Console\Mariadb\ConversorMysqlParaMariadb;
use Tests\Support\SaidaEmMemoria;
use Tests\Support\UsaPastaTemporaria;

#[CoversClass(MariadbGerarComando::class)]
final class MariadbGerarComandoTest extends TestCase
{
    use UsaPastaTemporaria;

    private SaidaEmMemoria $saida;

    protected function setUp(): void
    {
        $this->criarPasta();
        $this->saida = new SaidaEmMemoria();
        $this->escrever('src/database/base.sql', "DROP TABLE IF EXISTS t;\nCREATE TABLE t (id int) COLLATE=utf8mb4_0900_ai_ci;\n");
        $this->escrever('src/database/Migrations/Exemplo/20261125_103500_a.sql', "ALTER TABLE t ADD c varchar(5) COLLATE utf8mb4_0900_ai_ci;\n");
        $this->escrever('src/database/Migrations/Exemplo/20261126_080000_b.sql', "ALTER TABLE t ADD d int;\n");
    }

    protected function tearDown(): void
    {
        $this->apagarPasta();
    }

    private function comando(): MariadbGerarComando
    {
        return new MariadbGerarComando(
            $this->pasta,
            new ConversorMysqlParaMariadb(),
            $this->saida,
            static fn (): DateTimeImmutable => new DateTimeImmutable('2026-11-25 10:35:00')
        );
    }

    public function testSemArgumentoConverteOBaseSqlETodasAsMigrations(): void
    {
        $codigo = $this->comando()->executar([], []);

        $this->assertSame(0, $codigo);
        $this->assertFileExists($this->pasta . '/src/database/mariadb/base.sql');
        $this->assertFileExists($this->pasta . '/src/database/mariadb/Migrations/Exemplo/20261125_103500_a.sql');
        $this->assertFileExists($this->pasta . '/src/database/mariadb/Migrations/Exemplo/20261126_080000_b.sql');
    }

    public function testACopiaNaoTemMaisACollationDoMysql8(): void
    {
        $this->comando()->executar([], []);

        $copia = (string) file_get_contents($this->pasta . '/src/database/mariadb/base.sql');
        $this->assertStringNotContainsString('0900', $copia);
        $this->assertStringContainsString('utf8mb4_unicode_ci', $copia);
    }

    public function testOsOriginaisFicamIntactos(): void
    {
        $antes = (string) file_get_contents($this->pasta . '/src/database/base.sql');

        $this->comando()->executar([], []);

        $this->assertSame($antes, file_get_contents($this->pasta . '/src/database/base.sql'));
        $this->assertStringContainsString('0900', (string) file_get_contents($this->pasta . '/src/database/Migrations/Exemplo/20261125_103500_a.sql'));
    }

    public function testCopiaVemComCabecalhoDizendoQueEGeradaENaoDeveSerEditada(): void
    {
        $this->comando()->executar([], []);

        $copia = (string) file_get_contents($this->pasta . '/src/database/mariadb/Migrations/Exemplo/20261126_080000_b.sql');
        $this->assertStringStartsWith('-- ', $copia);
        $this->assertStringContainsString('Gerado por sgc', $copia);
        $this->assertStringContainsString('2026-11-25 10:35:00', $copia);
        $this->assertStringContainsString('Migrations/Exemplo/20261126_080000_b.sql', $copia);
        $this->assertStringContainsString('ALTER TABLE t ADD d int;', $copia);
    }

    public function testMostraAsTrocasEOAvisoDeDropDoBaseSql(): void
    {
        $this->comando()->executar([], []);
        $texto = $this->saida->texto();

        $this->assertStringContainsString('utf8mb4_0900_ai_ci → utf8mb4_unicode_ci', $texto);
        $this->assertStringContainsString('DROP TABLE', $texto);
        $this->assertStringContainsString('sem mudanças', $texto);
    }

    public function testComUmArquivoConverteSoEle(): void
    {
        $this->comando()->executar(['src/database/Migrations/Exemplo/20261125_103500_a.sql'], []);

        $this->assertFileExists($this->pasta . '/src/database/mariadb/Migrations/Exemplo/20261125_103500_a.sql');
        $this->assertFileDoesNotExist($this->pasta . '/src/database/mariadb/base.sql');
    }

    public function testPodeEscolherAPastaDeSaida(): void
    {
        $this->comando()->executar([], ['saida' => 'build/mariadb']);

        $this->assertFileExists($this->pasta . '/build/mariadb/base.sql');
    }

    public function testRecusaSaidaQueSobrescreveriaOsOriginais(): void
    {
        $this->expectException(InvalidArgumentException::class);

        try {
            $this->comando()->executar([], ['saida' => 'src/database']);
        } finally {
            $this->assertStringContainsString('0900', (string) file_get_contents($this->pasta . '/src/database/base.sql'));
        }
    }

    public function testRecusaArquivoQueNaoEhSqlOuNaoExiste(): void
    {
        $this->escrever('src/database/LEIAME.md', 'x');

        $this->expectException(InvalidArgumentException::class);

        $this->comando()->executar(['src/database/LEIAME.md'], []);
    }

    public function testRecusaArquivoForaDoProjeto(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->comando()->executar(['../fora.sql'], []);
    }

    public function testGerarDuasVezesAtualizaACopiaSemErro(): void
    {
        $this->comando()->executar([], []);
        $codigo = $this->comando()->executar([], []);

        $this->assertSame(0, $codigo);
    }
}
