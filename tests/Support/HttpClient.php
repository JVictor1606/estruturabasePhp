<?php

declare(strict_types=1);

namespace Tests\Support;

use CurlHandle;

/** Cliente HTTP mínimo com cookie jar (mantém a sessão) e sem seguir redirecionamentos. */
final class HttpClient
{
    private CurlHandle $curl;

    public function __construct(private readonly string $urlBase)
    {
        $this->curl = curl_init();
        curl_setopt_array($this->curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEFILE => '',
            CURLOPT_TIMEOUT => 15,
        ]);
    }

    public function get(string $caminho): HttpResponse
    {
        curl_setopt($this->curl, CURLOPT_HTTPGET, true);

        return $this->enviar($caminho);
    }

    /** @param array<string, string> $campos */
    public function post(string $caminho, array $campos): HttpResponse
    {
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($campos));

        return $this->enviar($caminho);
    }

    /** Valor atual de um cookie (ex.: PHPSESSID), ou null. */
    public function cookie(string $nome): ?string
    {
        foreach ((array) curl_getinfo($this->curl, CURLINFO_COOKIELIST) as $linha) {
            $campos = explode("\t", (string) $linha);
            if (($campos[5] ?? null) === $nome) {
                return $campos[6] ?? null;
            }
        }

        return null;
    }

    private function enviar(string $caminho): HttpResponse
    {
        curl_setopt($this->curl, CURLOPT_URL, $this->urlBase . $caminho);

        $resposta = curl_exec($this->curl);
        if ($resposta === false) {
            throw new \RuntimeException('Falha HTTP: ' . curl_error($this->curl));
        }

        $tamanhoCabecalho = (int) curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);

        return new HttpResponse(
            (int) curl_getinfo($this->curl, CURLINFO_RESPONSE_CODE),
            substr((string) $resposta, 0, $tamanhoCabecalho),
            substr((string) $resposta, $tamanhoCabecalho),
        );
    }
}
