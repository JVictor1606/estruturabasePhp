<?php

declare(strict_types=1);

use Src\Core\FileService;
use Src\Core\Router;

date_default_timezone_set('America/Sao_Paulo');

require __DIR__ . '/../vendor/autoload.php';

$dontEnv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dontEnv->load();

// APP_ESTRUTURA=modular roda a versão em monolito modular (pasta modular/); o padrão é src/.
$modular = ($_ENV['APP_ESTRUTURA'] ?? getenv('APP_ESTRUTURA') ?: 'src') === 'modular';

if ($modular) {
    \Modular\Core\Session::iniciar();
    \Modular\Core\SecurityHeaders::enviar();
    require __DIR__ . '/../modular/bootstrap.php';
    $routes = require __DIR__ . '/../modular/routes.php';
    $fileService = \Modular\Core\FileService::class;
    $router = new \Modular\Core\Router();
} else {
    session_start();
    require __DIR__ . '/../src/Core/Helpers.php';
    require __DIR__ . '/../src/config/dependencies.php';
    $routes = require __DIR__ . '/../src/routes/web.php';
    $fileService = FileService::class;
    $router = new Router();
}

$fileService::init(
    basePath: __DIR__ . '/../public/uploads',
    baseUrl: '/uploads'
);

$routes($router);
$router->run();
