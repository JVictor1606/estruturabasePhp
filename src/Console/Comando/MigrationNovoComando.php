<?php

declare(strict_types=1);

namespace Src\Console\Comando;

use InvalidArgumentException;
use Src\Console\Migracoes\GeradorDeMigracao;
use Src\Console\Saida;

final class MigrationNovoComando implements IComando
{
    public function __construct(
        private readonly GeradorDeMigracao $gerador,
        private readonly Saida $saida
    ) {
    }

    public function descricao(): string
    {
        return 'Cria o arquivo de uma migration:  migration:novo <Area> <nome>';
    }

    public function executar(array $argumentos, array $opcoes): int
    {
        if (count($argumentos) < 2) {
            throw new InvalidArgumentException('Uso: php bin/sgc.php migration:novo <Area> <nome>   (ex.: migration:novo Exemplo novos_campos)');
        }

        $caminho = $this->gerador->criar($argumentos[0], $argumentos[1]);

        $this->saida->escrever("Migration criada: {$caminho}");
        $this->saida->escrever('Escreva o SQL no arquivo, aplique no banco e depois registre com:');
        $this->saida->escrever("  php bin/sgc.php migration:marcar {$caminho} --sim");

        return 0;
    }
}
