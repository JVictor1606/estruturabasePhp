<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Migracoes;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Src\Console\Migracoes\RegistroEmArquivoDeMigracoes;
use Tests\Support\UsaPastaTemporaria;

#[CoversClass(RegistroEmArquivoDeMigracoes::class)]
final class RegistroEmArquivoDeMigracoesTest extends TestCase
{
    use UsaPastaTemporaria;

    private const ARQUIVO = 'estado/migrations.json';

    protected function setUp(): void
    {
        $this->criarPasta();
    }

    protected function tearDown(): void
    {
        $this->apagarPasta();
    }

    private function registro(): RegistroEmArquivoDeMigracoes
    {
        return new RegistroEmArquivoDeMigracoes(
            $this->pasta . '/' . self::ARQUIVO,
            static fn (): DateTimeImmutable => new DateTimeImmutable('2026-11-25 10:35:00')
        );
    }

    public function testSemOArquivoNadaFoiAplicadoENadaEhCriadoAoLer(): void
    {
        $this->assertSame([], $this->registro()->aplicadas());
        $this->assertSame([], $this->arquivosCriados());
    }

    public function testRegistrarCriaOArquivoComChecksumEHoraDoRelogio(): void
    {
        $this->registro()->registrar('Exemplo/a.sql', str_repeat('a', 64));

        $this->assertSame(
            ['Exemplo/a.sql' => ['checksum' => str_repeat('a', 64), 'executada_em' => '2026-11-25 10:35:00']],
            $this->registro()->aplicadas()
        );
    }

    public function testGuardaVariasMigrationsNaOrdemEmQueForamRegistradas(): void
    {
        $registro = $this->registro();
        $registro->registrar('Exemplo/b.sql', str_repeat('b', 64));
        $registro->registrar('Exemplo/a.sql', str_repeat('a', 64));

        $this->assertSame(['Exemplo/b.sql', 'Exemplo/a.sql'], array_keys($this->registro()->aplicadas()));
    }

    public function testNaoRegistraAMesmaMigrationDuasVezesENemAlteraOQueJaEstava(): void
    {
        $registro = $this->registro();
        $registro->registrar('Exemplo/a.sql', str_repeat('a', 64));

        try {
            $registro->registrar('Exemplo/a.sql', str_repeat('b', 64));
            $this->fail('Deveria recusar o registro duplicado.');
        } catch (RuntimeException) {
            $this->assertSame(str_repeat('a', 64), $this->registro()->aplicadas()['Exemplo/a.sql']['checksum']);
        }
    }

    public function testArquivoComJsonInvalidoViraErroClaroEIntactoNaoEhSobrescrito(): void
    {
        $this->escrever(self::ARQUIVO, '{isto nao e json');

        try {
            $this->registro()->registrar('Exemplo/a.sql', str_repeat('a', 64));
            $this->fail('Deveria recusar um registro corrompido.');
        } catch (RuntimeException $erro) {
            $this->assertStringContainsString('corrompido', $erro->getMessage());
            $this->assertSame('{isto nao e json', file_get_contents($this->pasta . '/' . self::ARQUIVO));
        }
    }

    public function testLerUmArquivoCorrompidoTambemAvisa(): void
    {
        $this->escrever(self::ARQUIVO, '[1, 2');

        $this->expectException(RuntimeException::class);

        $this->registro()->aplicadas();
    }

    public function testGravaJsonLegivelPorHumanos(): void
    {
        $this->registro()->registrar('Exemplo/a.sql', str_repeat('a', 64));
        $texto = (string) file_get_contents($this->pasta . '/' . self::ARQUIVO);

        $this->assertStringContainsString("\n", trim($texto), 'O JSON deve ter várias linhas (formatado).');
        $this->assertIsArray(json_decode($texto, true));
        $this->assertStringContainsString('Exemplo/a.sql', $texto, 'A barra do caminho não deve sair escapada.');
    }

    public function testSoEscreveNoProprioArquivoDeRegistro(): void
    {
        $this->registro()->registrar('Exemplo/a.sql', str_repeat('a', 64));

        $this->assertSame([self::ARQUIVO], $this->arquivosCriados());
    }

    public function testDescricaoApontaParaOArquivoUsado(): void
    {
        $this->assertStringContainsString('migrations.json', $this->registro()->descricao());
    }
}
