<?php

declare(strict_types=1);

namespace Modular\Shared\Http\Controllers\api;

use Modular\Shared\Docs\Swagger;
use Modular\Shared\Support\View;
use OpenApi\Attributes as OA;

#[OA\Info(title: 'Projeto API', version: '1.0.0')]
class DocsApiController
{
    public function json(): string
    {
        ob_clean();
        header('Content-Type: application/json');
        return Swagger::generateJson();
    }

    public function ui(): void
    {
        echo View::render('public/swagger');
        exit;
    }
}
