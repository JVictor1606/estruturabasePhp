<?php

declare(strict_types=1);

namespace Src\Console\Migracoes;

/** Uma linha do relatório: um arquivo e em que situação ele está. */
final class ItemDeMigracao
{
    public function __construct(
        public readonly string $caminho,
        public readonly string $situacao,
        public readonly ?string $executadaEm,
        public readonly bool $vazia
    ) {
    }
}
