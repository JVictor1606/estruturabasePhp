<?php

declare(strict_types=1);

namespace Tests\Unit\Frontend;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Guarda de consistência visual (análise estática, sem navegador).
 * Garante que o tema continua trocável em um só lugar (public/css/input.css):
 * as views usam só nomes por função (bg-primary, text-muted...) e componentes (btn, card...).
 */
#[CoversNothing]
final class ViewsDoTemaTest extends TestCase
{
    private const PALETAS = 'slate|gray|zinc|neutral|stone|red|rose|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink';
    private const PROPRIEDADES = 'bg|text|border|ring|divide|from|to|via|placeholder|fill|stroke|outline|decoration|accent|caret|shadow';

    /** @return array<string, array{string}> */
    public static function todasAsViews(): array
    {
        $casos = [];
        foreach (self::caminhosDasViews() as $caminho) {
            $casos[self::nomeCurto($caminho)] = [$caminho];
        }

        return $casos;
    }

    /** @return array<string, array{string}> */
    public static function viewsComBotaoVerSenha(): array
    {
        return array_filter(
            self::todasAsViews(),
            static fn (array $caso): bool => str_contains((string) file_get_contents($caso[0]), 'toggle-password')
        );
    }

    #[DataProvider('todasAsViews')]
    public function testViewNaoUsaPaletaCrua(string $caminho): void
    {
        $html = (string) file_get_contents($caminho);
        preg_match_all('~(?<![\w-])(?:[a-z0-9-]+:)*(?:' . self::PROPRIEDADES . ')-(?:' . self::PALETAS . ')-\d{2,3}(?:/\d+)?(?![\w-])~', $html, $achados);

        $this->assertSame(
            [],
            array_values(array_unique($achados[0])),
            'Use um nome por função (ex.: bg-primary, text-muted, border-line). Para mudar cores, edite só public/css/input.css.'
        );
    }

    #[DataProvider('todasAsViews')]
    public function testViewNaoUsaFundoBrancoLiteralNemCorEmEstiloInline(string $caminho): void
    {
        $html = (string) file_get_contents($caminho);

        $this->assertDoesNotMatchRegularExpression('~(?<![\w-])(?:[a-z0-9-]+:)*bg-white(?![\w/-])~', $html, 'Use bg-surface em vez de bg-white.');
        $this->assertDoesNotMatchRegularExpression('~style="[^"]*(?:color|background)\s*:[^"]*"~i', $html, 'Cor em style="" escapa do tema; use uma classe.');
        $this->assertDoesNotMatchRegularExpression('~(?:bg|text|border)-\[#[0-9a-fA-F]{3,8}\]~', $html, 'Cor fixa em colchetes escapa do tema; crie um token em input.css.');
    }

    #[DataProvider('todasAsViews')]
    public function testViewUsaUmaBibliotecaDeIconesSo(string $caminho): void
    {
        $html = (string) file_get_contents($caminho);

        $this->assertDoesNotMatchRegularExpression('~\bph ph-|phosphor~i', $html, 'Os ícones são Font Awesome (fa-solid fa-*).');
    }

    #[DataProvider('viewsComBotaoVerSenha')]
    public function testBotaoVerSenhaFicaNoWrapperEComRotuloParaLeitorDeTela(string $caminho): void
    {
        $html = (string) file_get_contents($caminho);
        preg_match_all('/<button\b[^>]*\bclass="toggle-password"[^>]*>/s', $html, $botoes);
        preg_match_all('/<div class="password-field\b/', $html, $wrappers);
        preg_match_all('/<input\b[^>]*\btype="password"[^>]*>/s', $html, $inputs);

        $this->assertNotEmpty($botoes[0], 'O botão deve usar apenas class="toggle-password" (a posição vem do CSS do tema).');
        $this->assertCount(count($inputs[0]), $wrappers[0], 'Cada input de senha precisa estar num <div class="password-field">.');

        foreach ($botoes[0] as $botao) {
            $this->assertStringContainsString('aria-label=', $botao, 'Sem aria-label o leitor de tela não sabe o que o botão faz.');
            $this->assertMatchesRegularExpression('/data-target="([^"]+)"/', $botao);
            preg_match('/data-target="([^"]+)"/', $botao, $alvo);
            $this->assertStringContainsString('id="' . $alvo[1] . '"', $html, "O data-target #{$alvo[1]} não existe na view.");
        }
    }

    #[DataProvider('viewsComBotaoVerSenha')]
    public function testInputDeSenhaUsaOComponenteDeCampo(string $caminho): void
    {
        $html = (string) file_get_contents($caminho);
        preg_match_all('/<input\b[^>]*\btype="password"[^>]*>/s', $html, $inputs);

        $this->assertNotEmpty($inputs[0]);
        foreach ($inputs[0] as $input) {
            $this->assertMatchesRegularExpression('/class="[^"]*\bfield-input\b/', $input, 'Input de senha usa .field-input (o espaço do olho vem de .password-field).');
        }
    }

    public function testCssCompiladoContemTudoQueAsViewsUsam(): void
    {
        $css = (string) file_get_contents(__DIR__ . '/../../../public/css/app.css');
        $nomesDoTema = $this->nomesDeCorDoTema();
        $componentes = $this->componentesDoTema();
        $faltando = [];

        foreach (self::caminhosDasViews() as $caminho) {
            $conteudo = (string) file_get_contents($caminho);

            // Componentes: só dentro de class="..." (palavras como "card" também aparecem em texto comum).
            preg_match_all('/class="([^"]*)"/', $conteudo, $atributos);
            $candidatos = [];
            foreach ($atributos[1] as $lista) {
                foreach (preg_split('/\s+/', trim($lista)) ?: [] as $token) {
                    $candidatos[$token] = true;
                }
            }

            // Cores do tema: no arquivo inteiro, para pegar classes montadas em variáveis PHP (menu, status, toast).
            preg_match_all('~(?<![\w-])((?:[a-z0-9-]+:)*(?:' . self::PROPRIEDADES . ')-[a-z][a-z0-9-]*(?:/\d+)?)(?![\w-])~', $conteudo, $utilitarios);
            foreach ($utilitarios[1] as $token) {
                $candidatos[$token] = true;
            }

            foreach (array_keys($candidatos) as $token) {
                if ($this->deveExistirNoCss($token, $nomesDoTema, $componentes) && ! str_contains($css, '.' . $this->escaparSeletor($token))) {
                    $faltando[$token] = true;
                }
            }
        }

        $this->assertSame([], array_keys($faltando), 'Falta no public/css/app.css. Rode: npm run build');
    }

    public function testJavaScriptTambemNaoUsaPaletaCrua(): void
    {
        $achados = [];
        foreach (glob(dirname(__DIR__, 3) . '/public/js/{,utils/}*.js', GLOB_BRACE) ?: [] as $arquivo) {
            preg_match_all('~(?<![\w-])(?:[a-z0-9-]+:)*(?:' . self::PROPRIEDADES . ')-(?:' . self::PALETAS . ')-\d{2,3}(?:/\d+)?(?![\w-])~', (string) file_get_contents($arquivo), $m);
            foreach ($m[0] as $token) {
                $achados[basename($arquivo)][$token] = true;
            }
        }

        $this->assertSame([], array_map('array_keys', $achados), 'Classes de cor adicionadas pelo JS também precisam usar os tokens do tema (ex.: border-danger, text-success).');
    }

    public function testTailwindEscaneiaAsViewsDoSrc(): void
    {
        $input = (string) file_get_contents(__DIR__ . '/../../../public/css/input.css');

        $this->assertStringContainsString('../../src/views', $input, 'Sem @source para src/views, classes usadas só ali não entram no app.css.');
    }

    public function testTemaDefineTodasAsCoresPorFuncaoNumLugarSo(): void
    {
        $input = (string) file_get_contents(__DIR__ . '/../../../public/css/input.css');

        foreach (['primary', 'primary-hover', 'on-primary', 'page', 'surface', 'surface-muted', 'line', 'line-strong', 'focus', 'ink', 'ink-soft', 'muted', 'faint', 'subtle', 'danger', 'danger-strong', 'success', 'success-strong', 'warning-strong'] as $nome) {
            $this->assertStringContainsString("--color-{$nome}:", $input, "Falta o token --color-{$nome} em input.css.");
        }
        $this->assertStringContainsString('--font-sans:', $input);
        $this->assertStringContainsString('--font-serif:', $input);
    }

    /** @return list<string> */
    private function nomesDeCorDoTema(): array
    {
        $input = (string) file_get_contents(__DIR__ . '/../../../public/css/input.css');
        preg_match_all('/--color-([a-z0-9-]+)\s*:/', $input, $achados);

        return array_values(array_filter($achados[1], static fn (string $nome): bool => ! preg_match('/^(?:' . self::PALETAS . ')-\d/', $nome)));
    }

    /** @return list<string> */
    private function componentesDoTema(): array
    {
        $input = (string) file_get_contents(__DIR__ . '/../../../public/css/input.css');
        preg_match_all('/^\s{4}\.([a-z][a-z0-9-]*)\s*\{/m', $input, $achados);

        return array_values(array_unique($achados[1]));
    }

    /** Vale para componentes do tema e utilitários que usam um token de cor do tema (bg-primary, hover:text-muted...). */
    private function deveExistirNoCss(string $token, array $nomesDoTema, array $componentes): bool
    {
        if (in_array($token, $componentes, true)) {
            return true;
        }

        $nucleo = (string) preg_replace('/^(?:[a-z0-9-]+:)*/', '', $token);
        foreach ($nomesDoTema as $nome) {
            if (preg_match('~^(?:' . self::PROPRIEDADES . ')-' . preg_quote($nome, '~') . '(?:/\d+)?$~', $nucleo) === 1) {
                return true;
            }
        }

        return false;
    }

    private function escaparSeletor(string $token): string
    {
        return (string) preg_replace('/([^a-zA-Z0-9_-])/', '\\\\$1', $token);
    }

    /** @return list<string> */
    private static function caminhosDasViews(): array
    {
        $raiz = dirname(__DIR__, 3) . '/src/views';
        $views = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS)) as $arquivo) {
            $caminho = str_replace('\\', '/', $arquivo->getPathname());
            if ($arquivo->getExtension() === 'php' && true) {
                $views[] = $caminho;
            }
        }
        sort($views);

        return $views;
    }

    private static function nomeCurto(string $caminho): string
    {
        return (string) preg_replace('~^.*/src/views/~', '', $caminho);
    }
}
