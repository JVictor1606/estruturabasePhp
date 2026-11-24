<?php

declare(strict_types=1);

use Modular\Core\Container;
use Modular\Core\Database;
use Modular\Modules\Auditoria\Services\IAuditoriaService;
use Modular\Modules\Usuarios\Services\IUserService;
use Modular\Modules\Usuarios\Services\UserService;
use Modular\Modules\Usuarios\Domain\IUserRepository;
use Modular\Modules\Usuarios\Http\Controllers\AdminController;
use Modular\Modules\Usuarios\Http\Controllers\ConfigController;
use Modular\Modules\Usuarios\Infrastructure\AuditoriaUser;
use Modular\Modules\Usuarios\Infrastructure\UserRepository;

Container::set(IUserRepository::class, static function () {
    return new AuditoriaUser(
        new UserRepository(Container::get(Database::class)),
        Container::get(IAuditoriaService::class)
    );
});

Container::set(IUserService::class, static function () {
    return new UserService(Container::get(IUserRepository::class));
});

Container::set(AdminController::class, static function () {
    return new AdminController(Container::get(IUserService::class));
});

Container::set(ConfigController::class, static function () {
    return new ConfigController(Container::get(IUserService::class));
});
