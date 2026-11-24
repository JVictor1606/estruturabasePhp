<?php

declare(strict_types=1);

namespace Tests\Support;

use Modular\Modules\Auth\Infrastructure\IEmailSender;

/** Guarda os e-mails em memória em vez de enviar. */
final class FakeEmailSender implements IEmailSender
{
    /** @var list<array{para: string, assunto: string, corpo: string}> */
    public array $enviados = [];

    public function send(string $destinatario, string $assunto, string $corpoHtml): void
    {
        $this->enviados[] = ['para' => $destinatario, 'assunto' => $assunto, 'corpo' => $corpoHtml];
    }

    /** Código de 6 dígitos do último e-mail enviado (o template o mostra como "123 - 456"). */
    public function ultimoCodigo(): string
    {
        $ultimo = end($this->enviados);
        $texto = strip_tags((string) ($ultimo['corpo'] ?? ''));
        preg_match('/(\d{3})\s*-\s*(\d{3})/', $texto, $achado);

        return ($achado[1] ?? '') . ($achado[2] ?? '');
    }
}
