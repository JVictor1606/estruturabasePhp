<?php

declare(strict_types=1);

use Modular\Core\Container;
use Modular\Modules\Auditoria\Services\IAuditoriaService;
use Modular\Modules\Exemplo\Services\IExemploService;
use Modular\Modules\Home\Http\Controllers\HomeController;

Container::set(HomeController::class, static function () {
    return new HomeController(
        Container::get(IAuditoriaService::class),
        Container::get(IExemploService::class)
    );
});
