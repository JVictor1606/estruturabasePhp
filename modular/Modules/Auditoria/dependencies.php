<?php

declare(strict_types=1);

use Modular\Core\Container;
use Modular\Core\Database;
use Modular\Modules\Auditoria\Services\AuditoriaService;
use Modular\Modules\Auditoria\Services\IAuditoriaService;
use Modular\Modules\Auditoria\Domain\IAuditoriaRepository;
use Modular\Modules\Auditoria\Infrastructure\AuditoriaRepository;

Container::set(IAuditoriaRepository::class, static function () {
    return new AuditoriaRepository(Container::get(Database::class));
});

Container::set(IAuditoriaService::class, static function () {
    return new AuditoriaService(Container::get(IAuditoriaRepository::class));
});
