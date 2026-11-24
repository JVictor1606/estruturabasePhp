<?php

declare(strict_types=1);

/**
 * Carrega as dependências: primeiro as compartilhadas, depois as de cada módulo.
 * Cada módulo registra o que é seu em Modules/<Modulo>/dependencies.php.
 */
require __DIR__ . '/Shared/Support/helpers.php';
require __DIR__ . '/Shared/dependencies.php';

foreach (glob(__DIR__ . '/Modules/*/dependencies.php') as $file) {
    require $file;
}
