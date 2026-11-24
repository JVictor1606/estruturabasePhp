<?php

declare(strict_types=1);

namespace Tests\Support;

final class HttpResponse
{
    public function __construct(
        public readonly int $status,
        private readonly string $cabecalhos,
        public readonly string $corpo,
    ) {
    }

    /** Caminho do redirecionamento (ex.: "/home"), ou null se a resposta não redireciona. */
    public function location(): ?string
    {
        if (preg_match('/^Location:\s*(\S+)/mi', $this->cabecalhos, $achado) !== 1) {
            return null;
        }

        return $achado[1];
    }

    /** Valor do primeiro cabeçalho com esse nome (sem diferenciar maiúsculas), ou null. */
    public function cabecalho(string $nome): ?string
    {
        return $this->cabecalhosComONome($nome)[0] ?? null;
    }

    /** @return list<string> todos os valores (ex.: vários Set-Cookie) */
    public function cabecalhosComONome(string $nome): array
    {
        preg_match_all('/^' . preg_quote($nome, '/') . ':\s*(.+?)\r?$/mi', $this->cabecalhos, $achados);

        return $achados[1];
    }

    public function contem(string $trecho): bool
    {
        return str_contains($this->corpo, $trecho);
    }

    /** Valor do campo escondido de CSRF presente no formulário, ou null. */
    public function tokenCsrf(): ?string
    {
        if (preg_match('/name="csrf"\s+value="([^"]+)"/', $this->corpo, $achado) !== 1) {
            return null;
        }

        return $achado[1];
    }
}
