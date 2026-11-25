<?php

declare(strict_types=1);

namespace Src\Console\Comando;

use Closure;
use DateTimeImmutable;
use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Src\Console\Mariadb\ConversorMysqlParaMariadb;
use Src\Console\Saida;

/** Gera cópias dos .sql (escritos para o MySQL) prontas para o MariaDB. Os originais nunca são alterados. */
final class MariadbGerarComando implements IComando
{
    private const PASTA_DO_BANCO = 'src/database';
    private const SAIDA_PADRAO = 'src/database/mariadb';

    /** @param Closure(): DateTimeImmutable $agora */
    public function __construct(
        private readonly string $raiz,
        private readonly ConversorMysqlParaMariadb $conversor,
        private readonly Saida $saida,
        private readonly Closure $agora
    ) {
    }

    public function descricao(): string
    {
        return 'Gera a cópia MariaDB do base.sql e das migrations:  mariadb:gerar [arquivo] [--saida=pasta]';
    }

    public function executar(array $argumentos, array $opcoes): int
    {
        $pastaDeSaida = $this->limpar(is_string($opcoes['saida'] ?? null) ? (string) $opcoes['saida'] : self::SAIDA_PADRAO);
        $origens = isset($argumentos[0]) ? [$this->validarOrigem($argumentos[0])] : $this->todasAsOrigens();

        // Confere TUDO antes de gravar qualquer coisa: nunca pode cair em cima de um original.
        $pares = [];
        foreach ($origens as $origem) {
            $destino = $pastaDeSaida . '/' . substr($origem, strlen(self::PASTA_DO_BANCO) + 1);
            if ($destino === $origem) {
                throw new InvalidArgumentException("A pasta de saída \"{$pastaDeSaida}\" sobrescreveria o original {$origem}. Use outra (ex.: --saida=src/database/mariadb).");
            }
            $pares[] = [$origem, $destino];
        }

        foreach ($pares as [$origem, $destino]) {
            $this->gerar($origem, $destino);
        }

        $this->saida->escrever();
        $this->saida->escrever(count($pares) . " arquivo(s) gerado(s) em {$pastaDeSaida}. Os originais não foram alterados.");
        $this->saida->escrever('Revise a cópia antes de rodar no MariaDB do servidor.');

        return 0;
    }

    private function gerar(string $origem, string $destino): void
    {
        $conteudo = (string) file_get_contents($this->raiz . '/' . $origem);
        $resultado = $this->conversor->converter($conteudo);
        $eol = str_contains($conteudo, "\r\n") ? "\r\n" : "\n";

        $cabecalho = implode($eol, [
            '-- Gerado por sgc mariadb:gerar em ' . ($this->agora)()->format('Y-m-d H:i:s') . " a partir de {$origem}",
            '-- Cópia para MariaDB. NÃO EDITE: altere o original (MySQL) e gere de novo.',
            '-- ----------------------------------------------------------------------------',
            '',
            '',
        ]);

        $arquivo = $this->raiz . '/' . $destino;
        if (! is_dir(dirname($arquivo)) && ! mkdir(dirname($arquivo), 0777, true) && ! is_dir(dirname($arquivo))) {
            throw new InvalidArgumentException("Não consegui criar a pasta {$destino}.");
        }
        file_put_contents($arquivo, $cabecalho . $resultado->sql);

        $this->saida->escrever("{$origem}  →  {$destino}");
        if ($resultado->mudancas === [] && $resultado->avisos === []) {
            $this->saida->escrever('    sem mudanças (já era compatível)');
        }
        foreach ($resultado->mudancas as $mudanca) {
            $this->saida->escrever('    ' . $mudanca);
        }
        foreach ($resultado->avisos as $aviso) {
            $this->saida->escrever('    ATENÇÃO: ' . $aviso);
        }
    }

    /** @return list<string> caminhos relativos à raiz do projeto */
    private function todasAsOrigens(): array
    {
        $origens = [];
        if (is_file($this->raiz . '/' . self::PASTA_DO_BANCO . '/base.sql')) {
            $origens[] = self::PASTA_DO_BANCO . '/base.sql';
        }

        $pasta = $this->raiz . '/' . self::PASTA_DO_BANCO . '/Migrations';
        if (is_dir($pasta)) {
            $achadas = [];
            $itens = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pasta, FilesystemIterator::SKIP_DOTS));
            foreach ($itens as $item) {
                if ($item->isFile() && strtolower($item->getExtension()) === 'sql') {
                    $achadas[] = self::PASTA_DO_BANCO . '/Migrations/' . substr(str_replace('\\', '/', $item->getPathname()), strlen(str_replace('\\', '/', $pasta)) + 1);
                }
            }
            sort($achadas);
            $origens = array_merge($origens, $achadas);
        }

        if ($origens === []) {
            throw new InvalidArgumentException('Não achei nenhum .sql em ' . self::PASTA_DO_BANCO . '.');
        }

        return $origens;
    }

    private function validarOrigem(string $caminho): string
    {
        $caminho = $this->limpar($caminho);

        if (strtolower(pathinfo($caminho, PATHINFO_EXTENSION)) !== 'sql') {
            throw new InvalidArgumentException("\"{$caminho}\" não é um arquivo .sql.");
        }
        if (! str_starts_with($caminho, self::PASTA_DO_BANCO . '/')) {
            throw new InvalidArgumentException('Só converto arquivos de ' . self::PASTA_DO_BANCO . '/ (base.sql e Migrations).');
        }
        if (! is_file($this->raiz . '/' . $caminho)) {
            throw new InvalidArgumentException("Arquivo não encontrado: {$caminho}");
        }

        return $caminho;
    }

    /** Caminho sempre relativo ao projeto, com "/", sem "..". */
    private function limpar(string $caminho): string
    {
        $caminho = trim(str_replace('\\', '/', $caminho), '/');
        if ($caminho === '' || preg_match('~(^|/)\.\.(/|$)~', $caminho) === 1 || preg_match('~^[A-Za-z]:~', $caminho) === 1) {
            throw new InvalidArgumentException("Caminho inválido: \"{$caminho}\". Use um caminho dentro do projeto.");
        }

        return $caminho;
    }
}
