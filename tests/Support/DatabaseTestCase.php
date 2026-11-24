<?php

declare(strict_types=1);

namespace Tests\Support;

use Modular\Core\Database;
use Modular\Modules\Usuarios\Infrastructure\UserRepository;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

/** Base dos testes que precisam de MySQL: banco *_test limpo antes de cada teste. */
abstract class DatabaseTestCase extends TestCase
{
    protected static PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        try {
            self::$pdo = TestDatabase::preparar();
        } catch (PDOException) {
            self::markTestSkipped('MySQL indisponível para os testes de integração (confira as variáveis DB_* do .env).');
        }
    }

    protected function setUp(): void
    {
        TestDatabase::limpar(self::$pdo);
    }

    protected function repositorio(): UserRepository
    {
        return new UserRepository(new Database());
    }
}
