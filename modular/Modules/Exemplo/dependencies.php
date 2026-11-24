<?php

declare(strict_types=1);

use Modular\Core\Container;
use Modular\Core\Database;
use Modular\Modules\Auditoria\Services\IAuditoriaService;
use Modular\Modules\Exemplo\Services\ExemploService;
use Modular\Modules\Exemplo\Services\IExemploService;
use Modular\Modules\Exemplo\Domain\IExemploRepository;
use Modular\Modules\Exemplo\Http\Controllers\ExemploController;
use Modular\Modules\Exemplo\Infrastructure\AuditoriaExemplo;
use Modular\Modules\Exemplo\Infrastructure\ExemploRepository;

Container::set(IExemploRepository::class, static function () {
    return new AuditoriaExemplo(
        new ExemploRepository(Container::get(Database::class)),
        Container::get(IAuditoriaService::class)
    );
});

Container::set(IExemploService::class, static function () {
    return new ExemploService(Container::get(IExemploRepository::class));
});

Container::set(ExemploController::class, static function () {
    return new ExemploController(Container::get(IExemploService::class));
});
