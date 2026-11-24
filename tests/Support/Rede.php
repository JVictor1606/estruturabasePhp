<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Modular\Core\Validations::email() consulta DNS (checkdnsrr). Testes que usam e-mail
 * válido dependem dessa consulta; sem rede eles são pulados em vez de falhar sem explicação.
 */
final class Rede
{
    private static ?bool $dnsDisponivel = null;

    public static function dnsDisponivel(): bool
    {
        return self::$dnsDisponivel ??= checkdnsrr('gmail.com', 'MX');
    }
}
