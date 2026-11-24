<?php

declare(strict_types=1);

namespace Src\App\Utils;

class View
{

    private const HTML_CONFIAVEL = ['content', 'header', 'footer', 'sidebar', 'toast'];

    public static function render($view, $vars = [])
    {
        $file = __DIR__ . '/../../views/' . $view . '.php';

        if (! file_exists($file)) {
            return '';
        }

        extract($vars);

        ob_start();
        include $file;
        $content = ob_get_clean();

        return self::substituirPlaceholders($content, $vars);
    }

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
}
