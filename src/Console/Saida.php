<?php

declare(strict_types=1);

namespace Src\Console;

/** Para onde os comandos escrevem. Trocar por uma versão em memória deixa tudo testável. */
interface Saida
{
    public function escrever(string $linha = ''): void;
}
