<?php

declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;

/** Sobe o app (src/) no servidor embutido do PHP, apontado para o banco de teste. */
final class LocalServer
{
    /** @param resource $processo */
    private function __construct(
        private $processo,
        private readonly string $url,
        private readonly string $arquivoDeEmails,
    ) {
    }

    public static function iniciar(string $bancoDeTeste): self
    {
        $porta = self::portaLivre();
        $log = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "servidor-testes-{$porta}.log";
        $arquivoDeEmails = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "emails-testes-{$porta}.jsonl";
        @unlink($arquivoDeEmails);
        $raiz = dirname(__DIR__, 2);

        $comando = [
            PHP_BINARY,
            '-d', 'auto_prepend_file=' . __DIR__ . DIRECTORY_SEPARATOR . 'http_prepend.php',
            '-S', "127.0.0.1:{$porta}",
            '-t', $raiz . DIRECTORY_SEPARATOR . 'public',
        ];

        $processo = proc_open(
            $comando,
            [0 => ['pipe', 'r'], 1 => ['file', $log, 'w'], 2 => ['redirect', 1]],
            $pipes,
            $raiz,
            array_merge(getenv(), ['TEST_DB_NAME' => $bancoDeTeste, 'TEST_EMAIL_ARQUIVO' => $arquivoDeEmails]),
        );

        if (! is_resource($processo)) {
            throw new RuntimeException('Não foi possível iniciar o servidor embutido do PHP.');
        }

        $servidor = new self($processo, "http://127.0.0.1:{$porta}", $arquivoDeEmails);
        $servidor->aguardarPronto($log);

        return $servidor;
    }

    public function url(): string
    {
        return $this->url;
    }

    /** @return list<array{para: string, assunto: string, corpo: string}> e-mails que o app "enviou" */
    public function emailsEnviados(): array
    {
        if (! file_exists($this->arquivoDeEmails)) {
            return [];
        }

        $linhas = file($this->arquivoDeEmails, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        return array_map(static fn (string $linha): array => json_decode($linha, true), $linhas);
    }

    public function limparEmails(): void
    {
        @unlink($this->arquivoDeEmails);
    }

    public function parar(): void
    {
        proc_terminate($this->processo);
        proc_close($this->processo);
        @unlink($this->arquivoDeEmails);
    }

    private function aguardarPronto(string $log): void
    {
        $porta = (int) parse_url($this->url, PHP_URL_PORT);

        for ($tentativa = 0; $tentativa < 50; $tentativa++) {
            $socket = @fsockopen('127.0.0.1', $porta, $codigo, $mensagem, 0.2);
            if ($socket !== false) {
                fclose($socket);
                return;
            }
            usleep(100_000);
        }

        $this->parar();
        throw new RuntimeException('Servidor de testes não respondeu. Log: ' . $log);
    }

    private static function portaLivre(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $codigo, $mensagem);
        if ($socket === false) {
            throw new RuntimeException('Sem porta livre para o servidor de testes.');
        }

        $porta = (int) explode(':', (string) stream_socket_get_name($socket, false))[1];
        fclose($socket);

        return $porta;
    }
}
