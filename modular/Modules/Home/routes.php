<?php

declare(strict_types=1);

use Modular\Modules\Auth\Http\Middleware\AuthMiddleware;
use Modular\Modules\Home\Http\Controllers\HomeController;

$router->get('/home', [HomeController::class, 'index'], [AuthMiddleware::class]);
