<?php

declare(strict_types=1);

namespace Src\Console\Mariadb;

final class ResultadoDaConversao
{
    /**
     * @param list<string> $mudancas o que foi trocado (ex.: "2× a → b")
     * @param list<string> $avisos o que a pessoa precisa saber/conferir
     */
    public function __construct(
        public readonly string $sql,
        public readonly array $mudancas,
        public readonly array $avisos
    ) {
    }
}
