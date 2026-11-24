<?php

declare(strict_types=1);

namespace Modular\Shared\Http;

interface IMiddleware
{
    public function handle(): void;
}
