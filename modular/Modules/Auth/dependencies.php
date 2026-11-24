<?php

declare(strict_types=1);

use Modular\Core\Container;
use Modular\Core\RateLimiting;
use Modular\Modules\Auth\Services\AuthService;
use Modular\Modules\Auth\Services\IAuthService;
use Modular\Modules\Auth\Services\IRecuperacaoSenhaService;
use Modular\Modules\Auth\Services\RecuperacaoSenhaService;
use Modular\Modules\Auth\Http\Controllers\LoginController;
use Modular\Modules\Auth\Http\Middleware\AdminMiddleware;
use Modular\Modules\Auth\Http\Middleware\AuthMiddleware;
use Modular\Modules\Auth\Http\Middleware\GuestMiddleware;
use Modular\Modules\Auth\Http\Middleware\LoginRateLimitingMiddleware;
use Modular\Modules\Auth\Infrastructure\ArquivoEmailSender;
use Modular\Modules\Auth\Infrastructure\HttpEmailSender;
use Modular\Modules\Auth\Infrastructure\IEmailSender;
use Modular\Modules\Usuarios\Services\IUserService;
use Modular\Modules\Usuarios\Domain\IUserRepository;

// EMAIL_DRIVER=arquivo grava os e-mails em EMAIL_ARQUIVO (dev/testes). Qualquer outro valor envia de verdade.
Container::set(IEmailSender::class, static function () {
    if (($_ENV['EMAIL_DRIVER'] ?? 'http') === 'arquivo') {
        return new ArquivoEmailSender($_ENV['EMAIL_ARQUIVO'] ?? sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'emails-dev.jsonl');
    }

    return new HttpEmailSender();
});

Container::set(IAuthService::class, static function () {
    return new AuthService(Container::get(IUserRepository::class));
});

Container::set(IRecuperacaoSenhaService::class, static function () {
    return new RecuperacaoSenhaService(
        Container::get(IUserRepository::class),
        Container::get(IEmailSender::class),
    );
});

Container::set(AuthMiddleware::class, static function () {
    return new AuthMiddleware(Container::get(IAuthService::class));
});

Container::set(AdminMiddleware::class, static function () {
    return new AdminMiddleware(Container::get(IAuthService::class));
});

Container::set(GuestMiddleware::class, static function () {
    return new GuestMiddleware(Container::get(IAuthService::class));
});

Container::set(LoginRateLimitingMiddleware::class, static function () {
    return new LoginRateLimitingMiddleware(Container::get(RateLimiting::class));
});

Container::set(LoginController::class, static function () {
    return new LoginController(
        Container::get(IAuthService::class),
        Container::get(IUserService::class),
        Container::get(IRecuperacaoSenhaService::class),
        Container::get(RateLimiting::class),
    );
});
