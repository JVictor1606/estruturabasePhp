<?php

declare(strict_types=1);

namespace Tests\Support;

use PDO;
use RuntimeException;

/**
 * Prepara o banco usado pelos testes de integração.
 *
 * Regra de segurança: só opera em banco cujo nome termina em "_test". O schema vem de
 * src/database/base.sql (fonte única), sem o CREATE DATABASE/USE que apontam para o banco real.
 */
final class TestDatabase
{
    private static bool $schemaAplicado = false;

    public static function nome(): string
    {
        // Idempotente: o bootstrap troca DB_NAME pelo nome de teste, então nome() pode ser chamado de novo.
        $base = $_ENV['DB_NAME_TEST'] ?? ($_ENV['DB_NAME'] ?? '');
        $nome = str_ends_with($base, '_test') ? $base : $base . '_test';

        if ($nome === '_test') {
            throw new RuntimeException('Defina DB_NAME no .env (ou DB_NAME_TEST) para os testes de integração.');
        }

        return $nome;
    }

    /** Cria o banco de teste (se faltar), aplica o schema uma vez por execução e devolve a conexão. */
    public static function preparar(): PDO
    {
        $nome = self::nome();

        $servidor = self::conectar();
        $servidor->exec("CREATE DATABASE IF NOT EXISTS `{$nome}` DEFAULT CHARACTER SET utf8mb4");

        $pdo = self::conectar($nome);
        self::garantirBancoDeTeste($pdo);

        if (! self::$schemaAplicado) {
            foreach (self::comandosDoSchema() as $comando) {
                $pdo->exec($comando);
            }
            self::$schemaAplicado = true;
        }

        return $pdo;
    }

    /** Esvazia as tabelas respeitando as chaves estrangeiras. */
    public static function limpar(PDO $pdo): void
    {
        self::garantirBancoDeTeste($pdo);

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['tb_logs_auditoria', 'tb_exemplo', 'tb_usuarios'] as $tabela) {
            $pdo->exec("TRUNCATE TABLE `{$tabela}`");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    private static function conectar(?string $banco = null): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $_ENV['DB_HOST'], $_ENV['DB_PORT']);
        if ($banco !== null) {
            $dsn .= ";dbname={$banco}";
        }

        return new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    private static function garantirBancoDeTeste(PDO $pdo): void
    {
        $atual = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();

        if (! str_ends_with($atual, '_test')) {
            throw new RuntimeException("Operação destrutiva recusada: o banco '{$atual}' não termina em _test.");
        }
    }

    /** @return list<string> */
    private static function comandosDoSchema(): array
    {
        $sql = str_replace("\r\n", "\n", (string) file_get_contents(__DIR__ . '/../../src/database/base.sql'));
        $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);

        $comandos = [];
        foreach (explode(";\n", $sql) as $comando) {
            $comando = trim($comando);
            $ignorar = $comando === ''
                || str_starts_with($comando, 'CREATE DATABASE')
                || str_starts_with($comando, 'USE ');

            if (! $ignorar) {
                $comandos[] = $comando;
            }
        }

        return $comandos;
    }
}
