<?php

declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;
use Src\Console\Migracoes\IRegistroDeMigracoes;

/** Faz o papel da tabela tb_migrations sem precisar de banco. */
final class RegistroEmMemoria implements IRegistroDeMigracoes
{
    /** @param array<string, array{checksum: string, executada_em: string}> $aplicadas */
    public function __construct(private array $aplicadas = [])
    {
    }

    public function aplicadas(): array
    {
        return $this->aplicadas;
    }

    public function registrar(string $caminho, string $checksum): void
    {
        if (isset($this->aplicadas[$caminho])) {
            throw new RuntimeException("Migration já registrada: {$caminho}");
        }
        $this->aplicadas[$caminho] = ['checksum' => $checksum, 'executada_em' => '2026-11-25 12:00:00'];
    }

    public function descricao(): string
    {
        return 'registro_em_memoria';
    }
}
