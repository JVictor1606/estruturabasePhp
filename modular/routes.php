<?php

declare(strict_types=1);

use Modular\Core\Csrf;
use Modular\Core\Router;

return static function (Router $router): void {
    Csrf::check();
    Csrf::create();

    // Cada módulo declara suas rotas em Modules/<Modulo>/routes.php
    foreach (glob(__DIR__ . '/Modules/*/routes.php') as $file) {
        require $file;
    }
};
