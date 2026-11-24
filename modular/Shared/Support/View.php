<?php

declare(strict_types=1);

namespace Modular\Shared\Support;

class View
{
    /**
     * Placeholders {{nome}} que recebem HTML já montado pelo próprio sistema (views internas).
     * Qualquer outro placeholder é texto e é escapado: nome de usuário, mensagens, títulos, URLs.
     */
    private const HTML_CONFIAVEL = ['content', 'header', 'footer', 'sidebar', 'toast'];

    public static function render($view, $vars = [])
    {
        $file = self::localizar($view);

        if ($file === null) {
            return '';
        }

        extract($vars);

        ob_start();
        include $file;
        $content = ob_get_clean();

        return self::substituirPlaceholders($content, $vars);
    }

    /**
     * Uma única passada: o valor inserido nunca é reexaminado, então um dado do usuário
     * contendo "{{toast}}" não é expandido. Variáveis que não existem permanecem como estão.
     */
    private static function substituirPlaceholders(string $html, array $vars): string
    {
        return (string) preg_replace_callback('/\{\{(\w+)\}\}/', static function (array $achado) use ($vars): string {
            $nome = $achado[1];

            if (! array_key_exists($nome, $vars) || ! (is_string($vars[$nome]) || is_numeric($vars[$nome]))) {
                return $achado[0];
            }

            $valor = (string) $vars[$nome];

            return in_array($nome, self::HTML_CONFIAVEL, true)
                ? $valor
                : htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
        }, $html);
    }

    /**
     * Cada módulo guarda suas views em Modules/<Modulo>/Views; o que é comum fica em Shared/Views.
     * O nome da view continua o mesmo ('Admin/index'), então os controllers não mudam.
     */
    private static function localizar(string $view): ?string
    {
        $raiz = dirname(__DIR__, 2);
        $candidatas = glob($raiz . '/Modules/*/Views/' . $view . '.php') ?: [];
        $candidatas[] = $raiz . '/Shared/Views/' . $view . '.php';

        foreach ($candidatas as $arquivo) {
            if (file_exists($arquivo)) {
                return $arquivo;
            }
        }

        return null;
    }
}
