<?php

declare(strict_types=1);

namespace Src\Console\Migracoes;

/** Cruza "o que existe em disco" com "o que o banco diz que já rodou". Sem banco, sem efeito colateral. */
final class AnaliseDeMigracoes
{
    public const APLICADA = 'aplicada';
    public const PENDENTE = 'pendente';
    public const ALTERADA = 'alterada';
    public const SEM_ARQUIVO = 'sem_arquivo';

    /**
     * @param list<Migracao> $arquivos já na ordem de aplicação
     * @param array<string, array{checksum: string, executada_em: string}> $registradas
     * @return list<ItemDeMigracao>
     */
    public static function analisar(array $arquivos, array $registradas): array
    {
        $itens = [];

        foreach ($arquivos as $arquivo) {
            $registro = $registradas[$arquivo->caminho] ?? null;

            $itens[] = match (true) {
                $registro === null => new ItemDeMigracao($arquivo->caminho, self::PENDENTE, null, $arquivo->vazia),
                $registro['checksum'] !== $arquivo->checksum => new ItemDeMigracao($arquivo->caminho, self::ALTERADA, $registro['executada_em'], $arquivo->vazia),
                default => new ItemDeMigracao($arquivo->caminho, self::APLICADA, $registro['executada_em'], $arquivo->vazia),
            };
        }

        $existentes = array_map(static fn (Migracao $m): string => $m->caminho, $arquivos);
        foreach ($registradas as $caminho => $registro) {
            if (! in_array($caminho, $existentes, true)) {
                $itens[] = new ItemDeMigracao($caminho, self::SEM_ARQUIVO, $registro['executada_em'], false);
            }
        }

        return $itens;
    }

    /** @param list<ItemDeMigracao> $itens */
    public static function temProblema(array $itens): bool
    {
        foreach ($itens as $item) {
            if ($item->situacao !== self::APLICADA) {
                return true;
            }
        }

        return false;
    }
}
