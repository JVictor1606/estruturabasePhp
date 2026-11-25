<?php

declare(strict_types=1);

namespace Src\Console\Migracoes;

/** Um arquivo .sql de migration encontrado em disco. */
final class Migracao
{
    public function __construct(
        /** Relativo à pasta de migrations, sempre com "/" (ex.: Exemplo/20261125_103500_x.sql). */
        public readonly string $caminho,
        public readonly string $checksum,
        /** True quando só há comentários/espaços: ainda não foi escrito nenhum SQL. */
        public readonly bool $vazia
    ) {
    }
}
