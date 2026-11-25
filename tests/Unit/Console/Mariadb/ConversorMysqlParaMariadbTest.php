<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Mariadb;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Src\Console\Mariadb\ConversorMysqlParaMariadb;
use Src\Console\Mariadb\ResultadoDaConversao;

#[CoversClass(ConversorMysqlParaMariadb::class)]
#[CoversClass(ResultadoDaConversao::class)]
final class ConversorMysqlParaMariadbTest extends TestCase
{
    private function converter(string $sql): ResultadoDaConversao
    {
        return (new ConversorMysqlParaMariadb())->converter($sql);
    }

    public function testTrocaACollationDoMysql8NoCreateDatabase(): void
    {
        $r = $this->converter("CREATE DATABASE IF NOT EXISTS `x`\n  DEFAULT CHARACTER SET utf8mb4\n  COLLATE utf8mb4_0900_ai_ci;");

        $this->assertStringContainsString('COLLATE utf8mb4_unicode_ci;', $r->sql);
        $this->assertStringNotContainsString('0900', $r->sql);
    }

    public function testTrocaACollationEmColunasETabelas(): void
    {
        $sql = "CREATE TABLE t (nome varchar(10) COLLATE utf8mb4_0900_ai_ci NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;";

        $r = $this->converter($sql);

        $this->assertSame(0, substr_count($r->sql, '0900'));
        $this->assertSame(2, substr_count($r->sql, 'utf8mb4_unicode_ci'));
        $this->assertStringContainsString('COLLATE=utf8mb4_unicode_ci', $r->sql);
    }

    public function testContaQuantasTrocasFez(): void
    {
        $r = $this->converter("a COLLATE utf8mb4_0900_ai_ci, b COLLATE utf8mb4_0900_ai_ci");

        $this->assertSame(['2× utf8mb4_0900_ai_ci → utf8mb4_unicode_ci'], $r->mudancas);
    }

    public function testCollationBinariaViraUtf8mb4Bin(): void
    {
        $r = $this->converter('x COLLATE utf8mb4_0900_bin');

        $this->assertStringContainsString('COLLATE utf8mb4_bin', $r->sql);
        $this->assertSame([], $r->avisos);
    }

    public function testCollationQueNaoTemEquivalenteExatoGeraAviso(): void
    {
        $r = $this->converter('x COLLATE utf8mb4_0900_as_cs, y COLLATE utf8mb4_0900_as_ci');

        $this->assertStringContainsString('COLLATE utf8mb4_bin', $r->sql);
        $this->assertStringContainsString('COLLATE utf8mb4_unicode_ci', $r->sql);
        $this->assertNotEmpty($r->avisos);
        $this->assertStringContainsString('aproxim', mb_strtolower(implode(' ', $r->avisos)));
    }

    public function testNaoDependeDeMaiusculasEMinusculas(): void
    {
        $r = $this->converter('collate UTF8MB4_0900_AI_CI');

        $this->assertStringNotContainsString('0900', strtolower($r->sql));
    }

    public function testSqlJaCompativelFicaIgualEsemMudancas(): void
    {
        $sql = "CREATE TABLE t (id int) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $r = $this->converter($sql);

        $this->assertSame($sql, $r->sql);
        $this->assertSame([], $r->mudancas);
    }

    public function testNaoMexeEmTextoDeDadosQueSoCitaAColecao(): void
    {
        $sql = "INSERT INTO t (txt) VALUES ('use utf8mb4_0900_ai_ci no MySQL');";

        $r = $this->converter($sql);

        $this->assertSame($sql, $r->sql);
    }

    public function testAvisaQuandoOArquivoApagaTabelasOuBanco(): void
    {
        $r = $this->converter("DROP TABLE IF EXISTS `a`;\nCREATE TABLE a (id int);");

        $this->assertStringContainsString('DROP TABLE', implode(' ', $r->avisos));
    }

    public function testAvisaDeDropDatabase(): void
    {
        $r = $this->converter('DROP DATABASE meu_banco;');

        $this->assertStringContainsString('DROP DATABASE', implode(' ', $r->avisos));
    }

    public function testPreservaOFimDeLinhaOriginal(): void
    {
        $r = $this->converter("a COLLATE utf8mb4_0900_ai_ci;\r\nb;\r\n");

        $this->assertSame("a COLLATE utf8mb4_unicode_ci;\r\nb;\r\n", $r->sql);
    }

    public function testConverterDuasVezesDaOMesmoResultado(): void
    {
        $uma = $this->converter('x COLLATE utf8mb4_0900_ai_ci');
        $duas = $this->converter($uma->sql);

        $this->assertSame($uma->sql, $duas->sql);
        $this->assertSame([], $duas->mudancas);
    }
}
