<?php

declare(strict_types=1);

namespace Modular\Modules\Auth\Http\Middleware;

use Modular\Modules\Auth\Services\IAuthService;
use Modular\Shared\Http\IMiddleware;
use Modular\Shared\Support\Url;

class GuestMiddleware implements IMiddleware
{
    private IAuthService $authService;
    public function __construct(IAuthService $authService)
    {
        $this->authService = $authService;
    }
    public function handle(): void
    {
        if (! isset($_SESSION['user_id'])) {
            return;
        }
        Url::redirect($this->authService->checkAdmin() ? '/admin' : '/home');
    }
}
