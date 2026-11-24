<?php

declare(strict_types=1);

namespace Modular\Core;

/**
 * Cabeçalhos HTTP de defesa. Um único lugar para ajustar quando uma nova origem externa
 * (CDN, fonte, imagem) precisar ser liberada: edite ORIGENS_* abaixo.
 */
final class SecurityHeaders
{
    /** Folhas de estilo e fontes carregadas de CDN. */
    private const ORIGENS_ESTILO = 'https://fonts.googleapis.com https://cdnjs.cloudflare.com';
    private const ORIGENS_FONTE = 'https://fonts.gstatic.com https://cdnjs.cloudflare.com';

    /** @return array<string, string> nome do cabeçalho => valor */
    public static function cabecalhos(bool $https): array
    {
        $politicaDeConteudo = implode('; ', [
            "default-src 'self'",
            // 'unsafe-inline': as views ainda têm <script> e style="" embutidos. Remover é a próxima melhoria.
            "script-src 'self' 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline' " . self::ORIGENS_ESTILO,
            "font-src 'self' " . self::ORIGENS_FONTE,
            "img-src 'self' data:",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);

        $cabecalhos = [
            'Content-Security-Policy' => $politicaDeConteudo,
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
        ];

        if ($https) {
            $cabecalhos['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        return $cabecalhos;
    }

    public static function enviar(): void
    {
        $https = ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        header_remove('X-Powered-By');
        foreach (self::cabecalhos($https) as $nome => $valor) {
            header("{$nome}: {$valor}");
        }
    }
}
