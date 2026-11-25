<?php

declare(strict_types=1);

namespace Src\Console\Comando;

use InvalidArgumentException;
use Src\Console\Modulos\GeradorDeModulo;
use Src\Console\Saida;

final class ModuloComando implements IComando
{
    public function __construct(
        private readonly GeradorDeModulo $gerador,
        private readonly Saida $saida
    ) {
    }

    public function descricao(): string
    {
        return 'Cria uma funcionalidade nova a partir do Exemplo:  modulo <Nome> [--com-tabela] [--dry-run]';
    }

    public function executar(array $argumentos, array $opcoes): int
    {
        if ($argumentos === []) {
            throw new InvalidArgumentException('Uso: php bin/sgc.php modulo <Nome> [--com-tabela] [--dry-run]   (ex.: modulo Produto)');
        }

        $simular = isset($opcoes['dry-run']) || isset($opcoes['simular']);
        $criados = $this->gerador->gerar($argumentos[0], isset($opcoes['com-tabela']), $simular);

        $this->saida->escrever($simular ? 'Simulação: nada foi criado. Criaria:' : 'Módulo criado:');
        foreach ($criados as $caminho) {
            $this->saida->escrever('  + ' . $caminho);
        }

        $this->saida->escrever();
        $this->saida->escrever('Falta combinar à mão (pontos compartilhados):');
        $this->saida->escrever('  - Menu lateral: views/Shared/sidebar.php e SharedController::getSidebar');
        if (! isset($opcoes['com-tabela'])) {
            $this->saida->escrever('  - A tabela do módulo (rode de novo com --com-tabela ou crie uma migration: migration:novo)');
        }
        $this->saida->escrever('  - Troque os campos de exemplo (titulo, descricao) pelos do seu domínio: procure os TODO.');

        return 0;
    }
}
