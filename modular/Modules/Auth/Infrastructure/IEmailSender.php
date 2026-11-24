<?php

declare(strict_types=1);

namespace Modular\Modules\Auth\Infrastructure;

interface IEmailSender
{
    public function send(string $destinatario, string $assunto, string $corpoHtml): void;
}
