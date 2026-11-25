<?php

declare(strict_types=1);

namespace Tests\Support;

use Src\Console\Saida;

/** Guarda o que o comando "imprimiria", para os testes conferirem. */
final class SaidaEmMemoria implements Saida
{
    /** @var list<string> */
    private array $linhas = [];

    public function escrever(string $linha = ''): void
    {
        $this->linhas[] = $linha;
    }

    public function texto(): string
    {
        return implode("\n", $this->linhas);
    }
}
