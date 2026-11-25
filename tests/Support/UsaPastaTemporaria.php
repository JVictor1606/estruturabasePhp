<?php

declare(strict_types=1);

namespace Tests\Support;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/** Cria uma pasta temporária por teste e a apaga no fim. Nunca toca no projeto de verdade. */
trait UsaPastaTemporaria
{
    private string $pasta = '';

    protected function criarPasta(): string
    {
        $this->pasta = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sgc-teste-' . bin2hex(random_bytes(6));
        mkdir($this->pasta, 0777, true);

        return $this->pasta;
    }

    protected function apagarPasta(): void
    {
        if ($this->pasta === '' || ! is_dir($this->pasta)) {
            return;
        }

        $itens = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->pasta, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($itens as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->pasta);
    }

    protected function escrever(string $caminhoRelativo, string $conteudo): void
    {
        $destino = $this->pasta . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $caminhoRelativo);
        if (! is_dir(dirname($destino))) {
            mkdir(dirname($destino), 0777, true);
        }
        file_put_contents($destino, $conteudo);
    }

    /** @return list<string> caminhos relativos (com /) de todos os arquivos da pasta temporária */
    protected function arquivosCriados(): array
    {
        $achados = [];
        $itens = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->pasta, FilesystemIterator::SKIP_DOTS));
        foreach ($itens as $item) {
            if ($item->isFile()) {
                $achados[] = str_replace('\\', '/', substr($item->getPathname(), strlen($this->pasta) + 1));
            }
        }
        sort($achados);

        return $achados;
    }
}
