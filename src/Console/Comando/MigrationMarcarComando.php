<?php

declare(strict_types=1);

namespace Src\Console\Comando;

use Closure;
use InvalidArgumentException;
use Src\Console\Migracoes\AnaliseDeMigracoes;
use Src\Console\Migracoes\CatalogoDeMigracoes;
use Src\Console\Migracoes\IRegistroDeMigracoes;
use Src\Console\Migracoes\Migracao;
use Src\Console\Saida;

/**
 * Registra que uma migration JÁ foi aplicada (por quem rodou o SQL na mão).
 * Não executa o SQL e não usa o banco: só anota no registro local, para o `migration:status` saber.
 */
final class MigrationMarcarComando implements IComando
{
    private const PREFIXO_DA_PASTA = 'src/database/Migrations/';

    /** @param Closure(): IRegistroDeMigracoes $fabricaDoRegistro */
    public function __construct(
        private readonly CatalogoDeMigracoes $catalogo,
        private readonly Closure $fabricaDoRegistro,
        private readonly Saida $saida
    ) {
    }

    public function descricao(): string
    {
        return 'Registra como aplicada (sem rodar o SQL):  migration:marcar <arquivo>|--todas --sim';
    }

    public function executar(array $argumentos, array $opcoes): int
    {
        $registro = ($this->fabricaDoRegistro)();
        $alvos = $this->alvos($argumentos, $opcoes, $registro);

        if ($alvos === []) {
            $this->saida->escrever('Nada a registrar: tudo já está registrado.');

            return 0;
        }

        if (! isset($opcoes['sim'])) {
            $this->saida->escrever('Registro: ' . $registro->descricao());
            $this->saida->escrever('Vou registrar como já aplicada(s):');
            foreach ($alvos as $alvo) {
                $this->saida->escrever('  - ' . $alvo->caminho);
            }
            $this->saida->escrever('Isto só anota no registro local; não roda o SQL e não mexe no banco. Rode de novo com --sim para confirmar.');

            return 1;
        }

        foreach ($alvos as $alvo) {
            $registro->registrar($alvo->caminho, $alvo->checksum);
            $this->saida->escrever('Registrada: ' . $alvo->caminho);
        }

        return 0;
    }

    /**
     * @param list<string> $argumentos
     * @param array<string, string|bool> $opcoes
     * @return list<Migracao>
     */
    private function alvos(array $argumentos, array $opcoes, IRegistroDeMigracoes $registro): array
    {
        $todas = $this->catalogo->todas();
        $aplicadas = $registro->aplicadas();

        if (isset($opcoes['todas'])) {
            $itens = AnaliseDeMigracoes::analisar($todas, $aplicadas);
            $pendentes = array_map(
                static fn ($i): string => $i->caminho,
                array_filter($itens, static fn ($i): bool => $i->situacao === AnaliseDeMigracoes::PENDENTE && ! $i->vazia)
            );

            return array_values(array_filter($todas, static fn (Migracao $m): bool => in_array($m->caminho, $pendentes, true)));
        }

        if ($argumentos === []) {
            throw new InvalidArgumentException('Uso: php bin/sgc.php migration:marcar <arquivo> --sim   (ou --todas)');
        }

        $caminho = $this->normalizar($argumentos[0]);
        $migracao = null;
        foreach ($todas as $candidata) {
            if ($candidata->caminho === $caminho) {
                $migracao = $candidata;
                break;
            }
        }

        if ($migracao === null) {
            throw new InvalidArgumentException("Migration não encontrada: {$caminho}. Veja a lista com migration:status.");
        }
        if ($migracao->vazia) {
            throw new InvalidArgumentException("A migration {$caminho} está vazia (só comentários). Escreva o SQL antes de registrar.");
        }
        if (isset($aplicadas[$migracao->caminho])) {
            $this->saida->escrever("A migration {$caminho} já está registrada. Nada a fazer.");

            return [];
        }

        return [$migracao];
    }

    private function normalizar(string $caminho): string
    {
        $caminho = str_replace('\\', '/', $caminho);

        return str_starts_with($caminho, self::PREFIXO_DA_PASTA) ? substr($caminho, strlen(self::PREFIXO_DA_PASTA)) : $caminho;
    }
}
