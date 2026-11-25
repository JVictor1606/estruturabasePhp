<?php

declare(strict_types=1);

namespace Src\Console\Comando;

interface IComando
{
    /** Uma linha que aparece na ajuda (`php bin/sgc.php`). */
    public function descricao(): string;

    /**
     * @param list<string> $argumentos o que não começa com --
     * @param array<string, string|bool> $opcoes --nome (true) ou --nome=valor
     * @return int 0 = deu certo; diferente de 0 = falhou ou há algo a corrigir
     */
    public function executar(array $argumentos, array $opcoes): int;
}
