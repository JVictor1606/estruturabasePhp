<?php

declare(strict_types=1);

/**
 * Carregado via auto_prepend_file pelo servidor embutido dos testes HTTP.
 * Roda ANTES do public/index.php: fixa o modo modular, o banco de teste e o e-mail em arquivo.
 * Como o Dotenv é "immutable", o que for definido aqui não é sobrescrito pelo .env.
 */
$banco = getenv('TEST_DB_NAME') ?: '';

if (! str_ends_with($banco, '_test')) {
    http_response_code(500);
    exit('Recusado: TEST_DB_NAME precisa terminar em _test.');
}

$_ENV['APP_ESTRUTURA'] = 'modular';
$_ENV['DB_NAME'] = $banco;

// Nunca enviar e-mail de verdade durante os testes.
$_ENV['EMAIL_DRIVER'] = 'arquivo';
$_ENV['EMAIL_ARQUIVO'] = getenv('TEST_EMAIL_ARQUIVO') ?: sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'emails-testes.jsonl';
