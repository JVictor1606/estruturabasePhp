<?php

declare(strict_types=1);

use Modular\Modules\Auth\Http\Middleware\AdminMiddleware;
use Modular\Modules\Auth\Http\Middleware\AuthMiddleware;
use Modular\Modules\Usuarios\Http\Controllers\AdminController;
use Modular\Modules\Usuarios\Http\Controllers\ConfigController;

$somenteAdmin = [AuthMiddleware::class, AdminMiddleware::class];

$router->get('/admin', [AdminController::class, 'index'], $somenteAdmin);
$router->get('/admin/novo', [AdminController::class, 'editar'], $somenteAdmin);
$router->post('/admin/novo', [AdminController::class, 'criar'], $somenteAdmin);
$router->get('/admin/editar/{id}', [AdminController::class, 'editar'], $somenteAdmin);
$router->post('/admin/editar/{id}', [AdminController::class, 'atualizar'], $somenteAdmin);
$router->post('/admin/deletar', [AdminController::class, 'deletar'], $somenteAdmin);
$router->get('/admin/detalhes/{id}', [AdminController::class, 'detalhes'], $somenteAdmin);

$router->get('/configuracoes', [ConfigController::class, 'index'], [AuthMiddleware::class]);
$router->post('/configuracoes/AtualizarSenha', [ConfigController::class, 'atualizarSenha'], [AuthMiddleware::class]);
