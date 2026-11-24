<?php

declare(strict_types=1);

namespace Modular\Modules\Auth\Http\Middleware;

use Modular\Modules\Auth\Services\IAuthService;
use Modular\Shared\Http\IMiddleware;
use Modular\Shared\Support\Url;

class AdminMiddleware implements IMiddleware
{
    private IAuthService $authService;

    public function __construct(IAuthService $authService)
    {
        $this->authService = $authService;
    }
    public function handle(): void
    {
        if (! $this->authService->checkAdmin()) {
            Url::redirect('/home');
        }
    }
}
