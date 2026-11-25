<?php

declare(strict_types=1);

namespace Src\Console;

use Src\Console\Comando\IComando;
use Throwable;

/** Lê a linha de comando, escolhe o comando certo e transforma erros em mensagens legíveis. */
final class Aplicacao
{
    /** @param array<string, IComando> $comandos nome => comando */
    public function __construct(
        private readonly array $comandos,
        private readonly Saida $saida
    ) {
    }

    /** @param list<string> $argv como vem do PHP: o primeiro item é o nome do script */
    public function executar(array $argv): int
    {
        $entrada = array_slice($argv, 1);

        // Atalho: `sgc --modulo Nome` equivale a `sgc modulo Nome`.
        if (($entrada[0] ?? '') === '--modulo') {
            $entrada[0] = 'modulo';
        }

        if ($entrada === []) {
            $this->mostrarAjuda();

            return 0;
        }

        $nome = array_shift($entrada);
        if (! isset($this->comandos[$nome])) {
            $this->saida->escrever("Comando desconhecido: {$nome}");
            $this->mostrarAjuda();

            return 1;
        }

        [$argumentos, $opcoes] = $this->separar($entrada);

        try {
            return $this->comandos[$nome]->executar($argumentos, $opcoes);
        } catch (Throwable $erro) {
            $this->saida->escrever('Erro: ' . $erro->getMessage());

            return 1;
        }
    }

    /**
     * @param list<string> $itens
     * @return array{0: list<string>, 1: array<string, string|bool>}
     */
    private function separar(array $itens): array
    {
        $argumentos = [];
        $opcoes = [];

        foreach ($itens as $item) {
            if (! str_starts_with($item, '--')) {
                $argumentos[] = $item;
                continue;
            }

            $sem = substr($item, 2);
            if (str_contains($sem, '=')) {
                [$chave, $valor] = explode('=', $sem, 2);
                $opcoes[$chave] = $valor;
                continue;
            }
            $opcoes[$sem] = true;
        }

        return [$argumentos, $opcoes];
    }

    private function mostrarAjuda(): void
    {
        $this->saida->escrever('Uso: php bin/sgc.php <comando> [argumentos] [--opcoes]');
        $this->saida->escrever();
        $this->saida->escrever('Comandos:');
        foreach ($this->comandos as $nome => $comando) {
            $this->saida->escrever(sprintf('  %-18s %s', $nome, $comando->descricao()));
        }
    }
}
