<?php

declare(strict_types=1);

namespace Src\Console\Migracoes;

use Closure;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

/** Cria o arquivo da migration com data/hora no nome. Não toca no banco. */
final class GeradorDeMigracao
{
    /** @param Closure(): DateTimeImmutable $agora */
    public function __construct(
        private readonly string $pasta,
        private readonly Closure $agora
    ) {
    }

    /** Caminho relativo que o arquivo teria (valida os nomes, mas não cria nada). */
    public function caminhoPara(string $area, string $nome): string
    {
        if (preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $area) !== 1) {
            throw new InvalidArgumentException("Área inválida: \"{$area}\". Use letras e números, começando por letra (ex.: Exemplo).");
        }

        $slug = preg_replace('/[\s-]+/', '_', mb_strtolower(trim($nome))) ?? '';
        if (preg_match('/^[a-z][a-z0-9_]{1,60}$/', $slug) !== 1) {
            throw new InvalidArgumentException("Nome inválido: \"{$nome}\". Use letras minúsculas, números e _ (ex.: novos_campos).");
        }

        return $area . '/' . ($this->agora)()->format('Ymd_His') . '_' . $slug . '.sql';
    }

    /** @return string caminho relativo do arquivo criado */
    public function criar(string $area, string $nome, string $sql = ''): string
    {
        $caminho = $this->caminhoPara($area, $nome);
        $destino = rtrim($this->pasta, '/\\') . '/' . $caminho;

        if (file_exists($destino)) {
            throw new RuntimeException("Já existe: {$caminho}. Não vou sobrescrever.");
        }
        if (! is_dir(dirname($destino)) && ! mkdir(dirname($destino), 0777, true) && ! is_dir(dirname($destino))) {
            throw new RuntimeException('Não consegui criar a pasta da migration.');
        }

        file_put_contents($destino, $this->cabecalho($area, $nome) . $sql);

        return $caminho;
    }

    private function cabecalho(string $area, string $nome): string
    {
        $linhas = [
            "-- Migration: {$nome} (área: {$area})",
            '-- Criada em: ' . ($this->agora)()->format('Y-m-d H:i:s'),
            '--',
            '-- Escreva o SQL abaixo desta linha. O banco local é MySQL e o do servidor é MariaDB.',
            '--  * NÃO use ADD COLUMN IF NOT EXISTS: o MySQL não aceita (o MariaDB aceita).',
            '--  * Prefira a collation utf8mb4_unicode_ci. As utf8mb4_0900_* só existem no MySQL 8;',
            '--    se usar, gere a cópia para o servidor com: php bin/sgc.php mariadb:gerar',
            '--  * Coluna nova em tabela que já tem linhas: NULL ou DEFAULT.',
            '--  * Uma ideia por arquivo. Depois de aplicada, não edite: crie outra migration.',
            '--  * Depois de rodar no banco, registre: php bin/sgc.php migration:marcar <arquivo> --sim',
            '-- ----------------------------------------------------------------------------',
            '',
            '',
        ];

        return implode("\n", $linhas);
    }
}
