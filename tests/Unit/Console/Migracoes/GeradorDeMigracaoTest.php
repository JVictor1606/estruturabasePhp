<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Migracoes;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Src\Console\Migracoes\GeradorDeMigracao;
use Tests\Support\UsaPastaTemporaria;

#[CoversClass(GeradorDeMigracao::class)]
final class GeradorDeMigracaoTest extends TestCase
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

    private function gerador(): GeradorDeMigracao
    {
        return new GeradorDeMigracao($this->pasta, static fn (): DateTimeImmutable => new DateTimeImmutable('2026-11-25 10:35:00'));
    }

    public function testNomeDoArquivoTemDataHoraEPastaDaArea(): void
    {
        $caminho = $this->gerador()->criar('Exemplo', 'novos_campos');

        $this->assertSame('Exemplo/20261125_103500_novos_campos.sql', $caminho);
        $this->assertFileExists($this->pasta . '/Exemplo/20261125_103500_novos_campos.sql');
    }

    public function testCriaAPastaDaAreaQuandoNaoExiste(): void
    {
        $this->gerador()->criar('Produto', 'criar_tabela');

        $this->assertDirectoryExists($this->pasta . '/Produto');
    }

    public function testCabecalhoAvisaSobreMysqlEMariadb(): void
    {
        $this->gerador()->criar('Exemplo', 'novos_campos');
        $conteudo = (string) file_get_contents($this->pasta . '/Exemplo/20261125_103500_novos_campos.sql');

        $this->assertStringContainsString('MySQL', $conteudo);
        $this->assertStringContainsString('MariaDB', $conteudo);
        $this->assertStringContainsString('IF NOT EXISTS', $conteudo);
        $this->assertStringContainsString('utf8mb4_unicode_ci', $conteudo);
        $this->assertStringContainsString('2026-11-25 10:35:00', $conteudo);
    }

    public function testNovoArquivoNasceVazioDeSqlParaNaoPassarPorPronto(): void
    {
        $this->gerador()->criar('Exemplo', 'novos_campos');
        $conteudo = (string) file_get_contents($this->pasta . '/Exemplo/20261125_103500_novos_campos.sql');

        $linhasDeSql = array_filter(array_map('trim', explode("\n", $conteudo)), static fn (string $l): bool => $l !== '' && ! str_starts_with($l, '--'));
        $this->assertSame([], array_values($linhasDeSql));
    }

    public function testAnexaOSqlInformado(): void
    {
        $this->gerador()->criar('Produto', 'criar_tabela', "CREATE TABLE tb_produto (id int);\n");

        $this->assertStringContainsString('CREATE TABLE tb_produto', (string) file_get_contents($this->pasta . '/Produto/20261125_103500_criar_tabela.sql'));
    }

    public function testNormalizaOSlugParaMinusculasComUnderline(): void
    {
        $caminho = $this->gerador()->criar('Exemplo', 'Novos Campos-da Tabela');

        $this->assertSame('Exemplo/20261125_103500_novos_campos_da_tabela.sql', $caminho);
    }

    #[DataProvider('nomesInvalidos')]
    public function testRejeitaNomeInvalido(string $area, string $nome): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->gerador()->criar($area, $nome);
    }

    /** @return array<string, array{string, string}> */
    public static function nomesInvalidos(): array
    {
        return [
            'nome vazio' => ['Exemplo', ''],
            'nome com barra' => ['Exemplo', 'a/b'],
            'nome subindo pasta' => ['Exemplo', '../x'],
            'nome de uma letra so' => ['Exemplo', 'a'],
            'nome com ponto' => ['Exemplo', 'a.sql'],
            'area vazia' => ['', 'novos_campos'],
            'area subindo pasta' => ['../etc', 'novos_campos'],
            'area com espaco' => ['Meu Modulo', 'novos_campos'],
        ];
    }

    public function testNuncaSobrescreveArquivoExistente(): void
    {
        $this->gerador()->criar('Exemplo', 'novos_campos');
        file_put_contents($this->pasta . '/Exemplo/20261125_103500_novos_campos.sql', 'MEU SQL');

        try {
            $this->gerador()->criar('Exemplo', 'novos_campos');
            $this->fail('Deveria recusar sobrescrever.');
        } catch (RuntimeException) {
            $this->assertSame('MEU SQL', file_get_contents($this->pasta . '/Exemplo/20261125_103500_novos_campos.sql'));
        }
    }
}
