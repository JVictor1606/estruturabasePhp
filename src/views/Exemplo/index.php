<?php

/**
 * @var array  $itens
 * @var string $urlCriar
 * @var string $editarUrl
 * @var string $deletarUrl
 */

?>

<div class="space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="page-title">Exemplo</h1>
            <p class="mt-1 text-sm text-muted">Gerencie os registros de exemplo</p>
        </div>
        <a href="<?= $urlCriar ?>"
            class="btn btn-primary">
            <i class="fa-solid fa-plus"></i>
            Novo Registro
        </a>
    </div>

    <?php if (empty($itens)) : ?>
    <div class="rounded-2xl border border-dashed border-line bg-surface p-10 text-center">
        <div class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-surface-muted text-ink-soft">
            <i class="fa-solid fa-folder-open"></i>
        </div>
        <p class="mt-3 text-sm font-semibold text-ink">Nenhum registro cadastrado.</p>
        <p class="mt-1 text-sm text-muted">Clique em "Novo Registro" para começar.</p>
    </div>
    <?php else : ?>
    <div class="rounded-2xl border border-line bg-surface">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-line">
                    <th class="px-5 py-3 text-left font-semibold text-ink-soft">Título</th>
                    <th class="px-5 py-3 text-left font-semibold text-ink-soft">Descrição</th>
                    <th class="px-5 py-3 text-left font-semibold text-ink-soft">Status</th>
                    <th class="px-5 py-3 text-right font-semibold text-ink-soft">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                <?php foreach ($itens as $item) : ?>
                <tr>
                    <td class="px-5 py-3 font-medium text-ink"><?= htmlspecialchars($item->getTitulo()) ?></td>
                    <td class="px-5 py-3 text-muted"><?= htmlspecialchars($item->getDescricao() ?? '—') ?></td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                            <?= $item->getStatus() ? 'bg-success-soft text-success-strong' : 'bg-danger-soft text-danger-strong' ?>">
                            <?= $item->getStatus() ? 'Ativo' : 'Inativo' ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="<?= $editarUrl . '?id=' . $item->getId() ?>"
                                class="inline-flex items-center gap-1 rounded-lg border border-line bg-surface px-3 py-1.5 text-xs font-semibold text-ink-soft hover:bg-page">
                                <i class="fa-solid fa-pen"></i> Editar
                            </a>
                            <button type="button"
                                class="js-btn-excluir inline-flex items-center gap-1 rounded-lg bg-danger px-3 py-1.5 text-xs font-semibold text-white hover:bg-danger-strong"
                                data-id="<?= htmlspecialchars($item->getId()) ?>"
                                data-nome="<?= htmlspecialchars($item->getTitulo()) ?>"
                                data-url="<?= $deletarUrl ?>"
                                data-campo="id">
                                <i class="fa-solid fa-trash"></i> Excluir
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
