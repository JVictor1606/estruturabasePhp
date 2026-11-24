<?php

declare(strict_types=1);

namespace Modular\Shared\Docs;

use Modular\Shared\Support\Url;
use OpenApi\Generator;

class Swagger
{
    public static function generateJson(): string
    {
        $openApi = (new Generator())->generate([
            __DIR__ . '/../Http/Controllers/api',
        ]);

        $spec = json_decode($openApi->toJson(), true);

        $basePath = Url::base();
        $scheme = ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $spec['servers'] = [
            [
                'url' => "{$scheme}://{$host}{$basePath}",
            ],
        ];
        return json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
