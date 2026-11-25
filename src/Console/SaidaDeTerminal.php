<?php

declare(strict_types=1);

namespace Src\Console;

final class SaidaDeTerminal implements Saida
{
    public function escrever(string $linha = ''): void
    {
        echo $linha . PHP_EOL;
    }
}
