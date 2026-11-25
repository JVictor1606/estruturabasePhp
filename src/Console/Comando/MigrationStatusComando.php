<?php

declare(strict_types=1);

namespace Src\Console\Comando;

use Closure;
use Src\Console\Migracoes\AnaliseDeMigracoes;
use Src\Console\Migracoes\CatalogoDeMigracoes;
use Src\Console\Migracoes\IRegistroDeMigracoes;
use Src\Console\Saida;

/** Só LÊ: mostra o que já foi registrado e o que falta. Não escreve em lugar nenhum e não usa o banco. */
final class MigrationStatusComando implements IComando
{
    /** @param Closure(): IRegistroDeMigracoes $fabricaDoRegistro abre o registro só quando preciso */
    public function __construct(
        private readonly CatalogoDeMigracoes $catalogo,
        private readonly Closure $fabricaDoRegistro,
        private readonly Saida $saida
    ) {
    }

    public function descricao(): string
    {
        return 'Mostra quais migrations já foram registradas e quais faltam';
    }

    public function executar(array $argumentos, array $opcoes): int
    {
        $registro = ($this->fabricaDoRegistro)();
        $this->saida->escrever('Registro: ' . $registro->descricao());

        $itens = AnaliseDeMigracoes::analisar($this->catalogo->todas(), $registro->aplicadas());
        if ($itens === []) {
            $this->saida->escrever('Nenhuma migration encontrada.');

            return 0;
        }

        $contagem = [];
        foreach ($itens as $item) {
            $contagem[$item->situacao] = ($contagem[$item->situacao] ?? 0) + 1;
            $this->saida->escrever(match ($item->situacao) {
                AnaliseDeMigracoes::APLICADA => sprintf('  [OK]       %s   aplicada em %s', $item->caminho, $item->executadaEm),
                AnaliseDeMigracoes::PENDENTE => sprintf('  [FALTA]    %s   pendente%s', $item->caminho, $item->vazia ? ' (arquivo vazio: ainda sem SQL)' : ''),
                AnaliseDeMigracoes::ALTERADA => sprintf('  [ALTERADA] %s   editada depois de aplicada em %s: não edite, crie outra', $item->caminho, $item->executadaEm),
                default => sprintf('  [SUMIU]    %s   registrada no banco, mas o arquivo não existe mais', $item->caminho),
            });
        }

        $this->saida->escrever();
        $this->saida->escrever('Resumo: ' . $this->resumo($contagem));

        return AnaliseDeMigracoes::temProblema($itens) ? 1 : 0;
    }

    /** @param array<string, int> $contagem */
    private function resumo(array $contagem): string
    {
        $partes = [];
        $aplicadas = $contagem[AnaliseDeMigracoes::APLICADA] ?? 0;
        $pendentes = $contagem[AnaliseDeMigracoes::PENDENTE] ?? 0;
        $partes[] = $aplicadas . ' aplicada' . ($aplicadas === 1 ? '' : 's');
        $partes[] = $pendentes . ' pendente' . ($pendentes === 1 ? '' : 's');
        if (($contagem[AnaliseDeMigracoes::ALTERADA] ?? 0) > 0) {
            $partes[] = $contagem[AnaliseDeMigracoes::ALTERADA] . ' alterada(s) depois de aplicadas';
        }
        if (($contagem[AnaliseDeMigracoes::SEM_ARQUIVO] ?? 0) > 0) {
            $partes[] = $contagem[AnaliseDeMigracoes::SEM_ARQUIVO] . ' sem arquivo';
        }

        return implode(', ', $partes);
    }
}
