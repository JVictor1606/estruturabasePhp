<?php

declare(strict_types=1);

namespace Src\Console\Migracoes;

use Closure;
use DateTimeImmutable;
use RuntimeException;

/**
 * Guarda o registro das migrations aplicadas num arquivo JSON local. NÃO usa o banco de dados.
 *
 * O arquivo é do ambiente (cada máquina tem o seu) e por isso fica fora do Git.
 */
final class RegistroEmArquivoDeMigracoes implements IRegistroDeMigracoes
{
    /** @param Closure(): DateTimeImmutable $agora */
    public function __construct(
        private readonly string $arquivo,
        private readonly Closure $agora
    ) {
    }

    public function aplicadas(): array
    {
        return $this->ler();
    }

    public function registrar(string $caminho, string $checksum): void
    {
        $aplicadas = $this->ler();

        if (isset($aplicadas[$caminho])) {
            throw new RuntimeException("Migration já registrada: {$caminho}");
        }

        $aplicadas[$caminho] = ['checksum' => $checksum, 'executada_em' => ($this->agora)()->format('Y-m-d H:i:s')];

        $this->gravar($aplicadas);
    }

    public function descricao(): string
    {
        return 'arquivo ' . str_replace('\\', '/', $this->arquivo);
    }

    /** @return array<string, array{checksum: string, executada_em: string}> */
    private function ler(): array
    {
        if (! is_file($this->arquivo)) {
            return [];
        }

        $dados = json_decode((string) file_get_contents($this->arquivo), true);
        if (! is_array($dados)) {
            throw new RuntimeException("O registro de migrations está corrompido ({$this->arquivo}). Corrija o arquivo ou apague-o e registre de novo.");
        }

        return $dados;
    }

    /** @param array<string, array{checksum: string, executada_em: string}> $aplicadas */
    private function gravar(array $aplicadas): void
    {
        $pasta = dirname($this->arquivo);
        if (! is_dir($pasta) && ! mkdir($pasta, 0777, true) && ! is_dir($pasta)) {
            throw new RuntimeException("Não consegui criar a pasta do registro de migrations: {$pasta}");
        }

        $json = json_encode($aplicadas, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (file_put_contents($this->arquivo, $json . "\n", LOCK_EX) === false) {
            throw new RuntimeException("Não consegui gravar o registro de migrations: {$this->arquivo}");
        }
    }
}
