<?php

use Modular\Shared\Support\Url;

?>

<div class="space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="mt-1 text-sm text-muted">Bem-vindo, <span class="font-medium text-ink">{{nome}}</span>.</p>
        </div>
    </div>

    <!-- TODO: adicione um card por módulo do seu projeto, seguindo este exemplo -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-muted">Exemplo</p>
                    <p class="stat-value">{{countExemplo}}</p>
                    <p class="mt-1 text-xs text-faint">Registros cadastrados</p>
                </div>
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-surface-muted text-ink-soft">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-line bg-surface">
        <div class="flex items-center justify-between gap-3 px-5 py-4">
            <h2 class="text-sm font-semibold text-ink">Últimos registros</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line">
                <thead class="bg-page">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Item</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Módulo</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-muted">Realizado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line bg-surface">
                    <?php if (empty($lastRegistros)) : ?>
                    <tr>
                        <td colspan="4" class="px-5 py-10 text-center text-sm text-faint">
                            Nenhuma atividade registrada.
                        </td>
                    </tr>
                    <?php else : ?>
                        <?php foreach ($lastRegistros as $log) : ?>
                            <?php
                            $acaoCores = [
                                'CREATE' => 'bg-success-soft text-success-strong',
                                'UPDATE' => 'bg-warning-soft text-warning-strong',
                                'DELETE' => 'bg-danger-soft text-danger-strong',
                            ];
                            $cor = $acaoCores[strtoupper($log['acao'])] ?? 'bg-surface-muted text-ink-soft';
                            ?>
                    <tr class="hover:bg-page">
                        <td class="px-5 py-4 text-sm font-medium text-ink">#<?= $log['registro_id'] ?></td>
                        <td class="px-5 py-4 text-sm text-muted"><?= htmlspecialchars($log['modulo']) ?></td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= $cor ?>">
                                <?= htmlspecialchars($log['acao']) ?>
                            </span>
                        </td>
                        <td class="px-2 py-4 text-right text-xs text-faint">
                            <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
                        </td>
                    </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>