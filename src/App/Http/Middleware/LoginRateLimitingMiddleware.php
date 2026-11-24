<?php

declare(strict_types=1);

namespace Src\App\Http\Middleware;

use Src\Core\RateLimiting;
use Src\App\Utils\Url;

/**
 * Recusa o login enquanto o par IP + e-mail estiver bloqueado. Só consulta: quem registra a
 * falha (ou zera, no sucesso) é o LoginController, que é quem sabe se a senha estava certa.
 */
class LoginRateLimitingMiddleware implements IMiddleware
{
    private RateLimiting $rateLimiting;

    public function __construct(RateLimiting $rateLimiting)
    {
        $this->rateLimiting = $rateLimiting;
    }

    public function handle(): void
    {
        $chave = RateLimiting::chave('login', (string) ($_POST['email'] ?? ''));
        $segundos = $this->rateLimiting->segundosRestantes($chave);

        if ($segundos > 0) {
            $_SESSION['erro_login'] = RateLimiting::mensagem($segundos);
            Url::redirect('/');
        }
    }
}
