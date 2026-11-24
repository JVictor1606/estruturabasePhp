<?php

declare(strict_types=1);

namespace Modular\Core;

use Closure;
use RuntimeException;

/**
 * Limita tentativas de ações sensíveis (login, recuperação de senha) com bloqueio progressivo.
 *
 * Só FALHAS contam. A partir de LIMITE_FALHAS, cada nova falha bloqueia por mais tempo
 * (DURACOES), o que desestimula adivinhação sem prender para sempre quem apenas errou a senha.
 * Durante o bloqueio a tentativa é recusada ANTES de verificar a senha, então acertar a senha
 * não adianta: senão o bloqueio não limitaria palpites. Sucesso zera o contador.
 */
class RateLimiting
{
    private const LIMITE_FALHAS = 5;

    /** Segundos de bloqueio a cada falha acima do limite; depois da última, repete o teto. */
    private const DURACOES = [60, 300, 900, 1800, 3600];

    /** Sem nenhuma falha por este tempo (depois do fim do bloqueio), o contador recomeça. */
    private const JANELA_DE_ESQUECIMENTO = 900;

    private Closure $relogio;

    public function __construct(
        private readonly string $arquivo = __DIR__ . '/../../Limiting/appLimiting.json',
        ?callable $relogio = null,
    ) {
        $this->relogio = $relogio !== null ? Closure::fromCallable($relogio) : static fn (): int => time();
    }

    /** Chave por escopo + IP + identificador (e-mail em minúsculas, sem espaços). */
    public static function chave(string $escopo, string $identificador): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';

        return $escopo . '#' . $ip . '#' . strtolower(trim($identificador));
    }

    public static function mensagem(int $segundos): string
    {
        if ($segundos < 60) {
            return "Muitas tentativas. Tente novamente em {$segundos} segundos.";
        }

        $minutos = (int) ceil($segundos / 60);

        return 'Muitas tentativas. Tente novamente em ' . $minutos . ($minutos === 1 ? ' minuto.' : ' minutos.');
    }

    public function segundosRestantes(string $chave): int
    {
        return $this->comTrava(function (array &$estado) use ($chave): int {
            return max(0, ($estado[$chave]['ate'] ?? 0) - $this->agora());
        });
    }

    public function registrarFalha(string $chave): void
    {
        $this->comTrava(function (array &$estado) use ($chave): void {
            $agora = $this->agora();
            $atual = $estado[$chave] ?? ['falhas' => 0, 'ultima' => 0, 'ate' => 0];

            $atual['falhas']++;
            $atual['ultima'] = $agora;

            if ($atual['falhas'] >= self::LIMITE_FALHAS) {
                $nivel = min($atual['falhas'] - self::LIMITE_FALHAS, count(self::DURACOES) - 1);
                $atual['ate'] = $agora + self::DURACOES[$nivel];
            }

            $estado[$chave] = $atual;
        });
    }

    public function limpar(string $chave): void
    {
        $this->comTrava(function (array &$estado) use ($chave): void {
            unset($estado[$chave]);
        });
    }

    private function agora(): int
    {
        return ($this->relogio)();
    }

    /** Lê, deixa o chamador alterar e grava, tudo sob trava exclusiva (requisições simultâneas). */
    private function comTrava(callable $operacao): mixed
    {
        $pasta = dirname($this->arquivo);
        if (! is_dir($pasta) && ! mkdir($pasta, 0775, true) && ! is_dir($pasta)) {
            throw new RuntimeException('Não foi possível criar a pasta do limitador de tentativas.');
        }

        $handle = fopen($this->arquivo, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Não foi possível abrir o arquivo do limitador de tentativas.');
        }

        try {
            flock($handle, LOCK_EX);

            $json = stream_get_contents($handle);
            $estado = $json !== false && $json !== '' ? json_decode($json, true) : [];
            $estado = is_array($estado) ? $this->descartarExpirados($estado) : [];

            $resultado = $operacao($estado);

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, (string) json_encode($estado));
            fflush($handle);

            return $resultado;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function descartarExpirados(array $estado): array
    {
        $limite = $this->agora() - self::JANELA_DE_ESQUECIMENTO;

        return array_filter(
            $estado,
            static fn (array $item): bool => max($item['ultima'] ?? 0, $item['ate'] ?? 0) > $limite
        );
    }
}
