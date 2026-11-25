<?php

declare(strict_types=1);

namespace Src\Console\Migracoes;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/** Lista as migrations que existem na pasta, na ordem em que devem ser aplicadas. */
final class CatalogoDeMigracoes
{
    public function __construct(private readonly string $pasta)
    {
    }

    /**
     * A ordem é global: pelo NOME do arquivo (a data/hora vem primeiro), não pela pasta.
     *
     * @return list<Migracao>
     */
    public function todas(): array
    {
        if (! is_dir($this->pasta)) {
            return [];
        }

        $base = rtrim(str_replace('\\', '/', $this->pasta), '/') . '/';
        $achadas = [];
        $itens = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->pasta, FilesystemIterator::SKIP_DOTS));

        foreach ($itens as $item) {
            if (! $item->isFile() || strtolower($item->getExtension()) !== 'sql') {
                continue;
            }

            $conteudo = (string) file_get_contents($item->getPathname());
            $caminho = substr(str_replace('\\', '/', $item->getPathname()), strlen($base));
            $achadas[] = new Migracao($caminho, self::checksum($conteudo), self::semSql($conteudo));
        }

        usort($achadas, static fn (Migracao $a, Migracao $b): int => [basename($a->caminho), $a->caminho] <=> [basename($b->caminho), $b->caminho]);

        return $achadas;
    }

    /** Impressão digital do conteúdo. Ignora BOM e o tipo de fim de linha (Windows x Linux). */
    public static function checksum(string $conteudo): string
    {
        $normalizado = str_replace(["\r\n", "\r"], "\n", preg_replace('/^\xEF\xBB\xBF/', '', $conteudo) ?? $conteudo);

        return hash('sha256', $normalizado);
    }

    private static function semSql(string $conteudo): bool
    {
        $semBlocos = preg_replace('~/\*.*?\*/~s', '', $conteudo) ?? $conteudo;
        $semLinhas = preg_replace('~^\s*(--|#).*$~m', '', $semBlocos) ?? $semBlocos;

        return trim($semLinhas) === '';
    }
}
