<?php

declare(strict_types=1);

namespace Src\App\Infrastructure;

use RuntimeException;

/**
 * Em vez de enviar, grava cada e-mail como uma linha JSON em um arquivo.
 * Use no desenvolvimento e nos testes (EMAIL_DRIVER=arquivo) para não disparar e-mail de verdade.
 */
class ArquivoEmailSender implements IEmailSender
{
    public function __construct(private readonly string $arquivo)
    {
    }

    public function send(string $destinatario, string $assunto, string $corpoHtml): void
    {
        $linha = json_encode(['para' => $destinatario, 'assunto' => $assunto, 'corpo' => $corpoHtml], JSON_UNESCAPED_UNICODE);

        if ($linha === false || file_put_contents($this->arquivo, $linha . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('Não foi possível gravar o e-mail no arquivo de desenvolvimento.');
        }
    }
}
