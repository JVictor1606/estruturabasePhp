<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Modulos;

use DateTimeImmutable;
use FilesystemIterator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Src\Console\Modulos\GeradorDeModulo;
use Tests\Support\UsaPastaTemporaria;

#[CoversClass(GeradorDeModulo::class)]
final class GeradorDeModuloTest extends TestCase
{
    use UsaPastaTemporaria;

    private string $raizReal;

    protected function setUp(): void
    {
        $this->raizReal = dirname(__DIR__, 4);
        $this->criarPasta();

        // Projeto de mentira: só os arquivos do modelo (Exemplo), copiados do projeto de verdade.
        foreach (GeradorDeModulo::ARQUIVOS_DO_MODELO as $arquivo) {
            $this->escrever($arquivo, (string) file_get_contents($this->raizReal . '/' . $arquivo));
        }
    }

    protected function tearDown(): void
    {
        $this->apagarPasta();
    }

    private function gerador(): GeradorDeModulo
    {
        return new GeradorDeModulo($this->pasta, static fn (): DateTimeImmutable => new DateTimeImmutable('2026-11-25 10:35:00'));
    }

    /** @return list<string> */
    private function novosArquivos(): array
    {
        return array_values(array_filter(
            $this->arquivosCriados(),
            static fn (string $caminho): bool => ! in_array($caminho, GeradorDeModulo::ARQUIVOS_DO_MODELO, true)
        ));
    }

    public function testGeraUmArquivoParaCadaPecaDoModelo(): void
    {
        $criados = $this->gerador()->gerar('Produto');

        $this->assertCount(count(GeradorDeModulo::ARQUIVOS_DO_MODELO), $criados);
        $this->assertContains('src/App/Http/Controllers/ProdutoController.php', $criados);
        $this->assertContains('src/App/Models/Produto.php', $criados);
        $this->assertContains('src/App/Services/IServices/IProdutoService.php', $criados);
        $this->assertContains('src/routes/modulos/produto.php', $criados);
        $this->assertContains('src/config/dependencies/modulos/produto.php', $criados);
        $this->assertContains('src/views/Produto/index.php', $criados);
        $this->assertContains('src/App/Dto/Produto/CriarProdutoDto.php', $criados);
        $this->assertEqualsCanonicalizing($criados, $this->novosArquivos(), 'A lista devolvida deve ser exatamente o que foi gravado.');
    }

    public function testNenhumTextoDoModeloSobraNosArquivosGerados(): void
    {
        $this->gerador()->gerar('Produto');

        foreach ($this->novosArquivos() as $caminho) {
            $conteudo = (string) file_get_contents($this->pasta . '/' . $caminho);
            $this->assertStringNotContainsStringIgnoringCase('exemplo', $conteudo, "Sobrou 'Exemplo' em {$caminho}");
            $this->assertStringNotContainsStringIgnoringCase('exemplo', $caminho);
        }
    }

    public function testClassesGanhamONomeNovoENamespaceCorreto(): void
    {
        $this->gerador()->gerar('Produto');
        $controller = (string) file_get_contents($this->pasta . '/src/App/Http/Controllers/ProdutoController.php');

        $this->assertStringContainsString('class ProdutoController', $controller);
        $this->assertStringContainsString('namespace Src\App\Http\Controllers;', $controller);
        $this->assertStringContainsString('Src\App\Services\IServices\IProdutoService', $controller);
    }

    public function testRotasEUrlsUsamONomeEmMinusculas(): void
    {
        $this->gerador()->gerar('Produto');
        $rotas = (string) file_get_contents($this->pasta . '/src/routes/modulos/produto.php');

        $this->assertStringContainsString("'/produto'", $rotas);
        $this->assertStringContainsString("'/produto/criar'", $rotas);
    }

    public function testNomeComPalavrasUsaUnderlineEmArquivosETabelas(): void
    {
        $criados = $this->gerador()->gerar('ControleDeEstoque');

        $this->assertContains('src/App/Http/Controllers/ControleDeEstoqueController.php', $criados);
        $this->assertContains('src/routes/modulos/controle_de_estoque.php', $criados);
        $repositorio = (string) file_get_contents($this->pasta . '/src/App/Infrastructure/Repositories/ControleDeEstoqueRepository.php');
        $this->assertStringContainsString('tb_controle_de_estoque', $repositorio);
    }

    public function testTodosOsArquivosPhpGeradosTemSintaxeValida(): void
    {
        $this->gerador()->gerar('Produto');

        foreach ($this->novosArquivos() as $caminho) {
            if (! str_ends_with($caminho, '.php')) {
                continue;
            }
            $saida = [];
            $codigo = 0;
            exec('"' . PHP_BINARY . '" -l ' . escapeshellarg($this->pasta . '/' . $caminho) . ' 2>&1', $saida, $codigo);
            $this->assertSame(0, $codigo, "Sintaxe inválida em {$caminho}: " . implode(' ', $saida));
        }
    }

    #[DataProvider('nomesInvalidos')]
    public function testRejeitaNomeInvalido(string $nome): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->gerador()->gerar($nome);
    }

    /** @return array<string, array{string}> */
    public static function nomesInvalidos(): array
    {
        return [
            'minuscula' => ['produto'],
            'vazio' => [''],
            'com hifen' => ['Produto-Novo'],
            'com espaco' => ['Meu Produto'],
            'subindo pasta' => ['../Hack'],
            'com barra' => ['A/B'],
            'comeca com numero' => ['1Produto'],
            'o proprio modelo' => ['Exemplo'],
            'sigla toda maiuscula' => ['PDF'],
        ];
    }

    public function testRecusaQuandoJaExisteEEscreveNada(): void
    {
        $this->gerador()->gerar('Produto');
        $antes = $this->arquivosCriados();

        try {
            $this->gerador()->gerar('Produto');
            $this->fail('Deveria recusar um módulo que já existe.');
        } catch (RuntimeException) {
            $this->assertSame($antes, $this->arquivosCriados());
        }
    }

    public function testRecusaSeUmUnicoDestinoJaExisteSemCriarOsOutros(): void
    {
        $this->escrever('src/App/Models/Produto.php', '<?php // do usuario');
        $antes = $this->arquivosCriados();

        try {
            $this->gerador()->gerar('Produto');
            $this->fail('Deveria recusar.');
        } catch (RuntimeException) {
            $this->assertSame($antes, $this->arquivosCriados());
            $this->assertSame('<?php // do usuario', file_get_contents($this->pasta . '/src/App/Models/Produto.php'));
        }
    }

    public function testSimularListaMasNaoCriaNada(): void
    {
        $antes = $this->arquivosCriados();

        $lista = $this->gerador()->gerar('Produto', true, true);

        $this->assertNotEmpty($lista);
        $this->assertSame($antes, $this->arquivosCriados());
    }

    public function testComTabelaCriaAMigrationDeCreateTable(): void
    {
        $criados = $this->gerador()->gerar('Produto', true);

        $migration = 'src/database/Migrations/Produto/20261125_103500_criar_tabela_produto.sql';
        $this->assertContains($migration, $criados);
        $sql = (string) file_get_contents($this->pasta . '/' . $migration);
        $this->assertStringContainsString('CREATE TABLE tb_produto', $sql);
        $this->assertStringContainsString('`version`', $sql);
        $this->assertStringContainsString('utf8mb4_unicode_ci', $sql);

        // O cabeçalho (comentário) cita a collation do MySQL 8 só para avisar; o SQL executável não pode usá-la.
        $executavel = implode("\n", array_filter(explode("\n", $sql), static fn (string $linha): bool => ! str_starts_with(trim($linha), '--')));
        $this->assertStringNotContainsString('0900', $executavel);
    }

    public function testSemTabelaNaoCriaMigration(): void
    {
        $criados = $this->gerador()->gerar('Produto');

        foreach ($criados as $caminho) {
            $this->assertStringNotContainsString('Migrations', $caminho);
        }
    }

    public function testListaDoModeloAcompanhaTudoQueExisteNoProjetoComNomeExemplo(): void
    {
        $raiz = dirname(__DIR__, 4);
        $achados = [];
        $itens = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz . '/src', FilesystemIterator::SKIP_DOTS));
        foreach ($itens as $item) {
            $relativo = str_replace('\\', '/', substr($item->getPathname(), strlen($raiz) + 1));
            // src/database/ (migrations e cópias MariaDB geradas) é SQL, não faz parte do modelo de código.
            if ($item->isFile() && stripos($relativo, 'exemplo') !== false && ! str_starts_with($relativo, 'src/database/')) {
                $achados[] = $relativo;
            }
        }
        sort($achados);
        $modelo = GeradorDeModulo::ARQUIVOS_DO_MODELO;
        sort($modelo);

        $this->assertSame($modelo, $achados, 'Surgiu (ou sumiu) um arquivo "Exemplo": atualize GeradorDeModulo::ARQUIVOS_DO_MODELO.');
    }
}
