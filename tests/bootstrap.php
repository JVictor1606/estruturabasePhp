<?php

declare(strict_types=1);

use Tests\Support\TestDatabase;

require __DIR__ . '/../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->safeLoad();

// Todo código de teste que abrir conexão (Modular\Core\Database lê $_ENV) cai no banco *_test.
// O banco de desenvolvimento nunca é usado pelos testes.
$_ENV['DB_NAME'] = TestDatabase::nome();
