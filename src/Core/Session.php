<?php

declare(strict_types=1);

namespace Src\Core;

/** Inicia a sessão com o cookie endurecido (antes era o padrão do PHP: legível por JavaScript e sem SameSite). */
final class Session
{
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Strict mode: o PHP recusa um id de sessão que ele mesmo não criou (fixação de sessão).
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_set_cookie_params(self::opcoesDoCookie());
        session_start();
    }

    /** @return array{lifetime: int, path: string, secure: bool, httponly: bool, samesite: string} */
    public static function opcoesDoCookie(?bool $https = null): array
    {
        $https ??= ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        return [
            'lifetime' => 0,
            'path' => '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }
}
