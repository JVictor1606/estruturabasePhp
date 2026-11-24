<?php

use Src\App\Enums\Status;
use Src\App\Utils\Url;

$users = $users ?? [];

$totalUsers = count($users);
$ativos = count(array_filter($users, fn($u) => $u->getStatus() === Status::ATIVO));
$inativos = $totalUsers - $ativos;
$admins = count(array_filter($users, fn($u) => $u->isAdmin()));
?>

<div class="space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="page-title">Administração</h1>
            <p class="mt-1 text-sm text-muted">Gerencie os usuários do sistema</p>
        </div>
        <a href="<?= Url::path('/admin/novo') ?>" class="inline-flex items-center gap-2 rounded-xl bg-[var(--color-primary)]  px-4 py-2 text-sm font-semibold text-white hover:text-white hover:bg-[var(--color-primary-hover)] ">
            <i class="fa-solid fa-user-plus"></i>
            Novo usuário
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-muted">Total</p>
                    <p class="stat-value"><?= $totalUsers ?></p>
                    <p class="mt-1 text-xs text-faint">Usuários cadastrados</p>
                </div>
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-surface-muted text-ink-soft">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-muted">Ativos</p>
                    <p class="stat-value"><?= $ativos ?></p>
                    <p class="mt-1 text-xs text-faint">Status ativo</p>
                </div>
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-success-soft text-success-strong">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-muted">Inativos</p>
                    <p class="stat-value"><?= $inativos ?></p>
                    <p class="mt-1 text-xs text-faint">Status inativo</p>
                </div>
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-danger-soft text-danger-strong">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-muted">Admins</p>
                    <p class="stat-value"><?= $admins ?></p>
                    <p class="mt-1 text-xs text-faint">Perfil administrador</p>
                </div>
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-warning-soft text-warning-strong">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
            <div class="lg:col-span-1">
                <label for="searchInput" class="field-label">Buscar</label>
                <div class="mt-1 flex items-center gap-2">
                    <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-surface-muted text-ink-soft">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <input type="text" id="searchInput" class="field-input" placeholder="Buscar por nome ou email...">
                </div>
            </div>

            <div class="lg:col-span-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label for="filterRole" class="field-label">Perfil</label>
                    <select id="filterRole" class="mt-1 h-10 w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-line-strong focus:outline-none focus:ring-4 focus:ring-focus">
                        <option value=''>Todos os perfis</option>
                        <option value="1">Usuário</option>
                        <option value="2">Admin</option>
                    </select>
                </div>
                <div>
                    <label for="filterStatus" class="field-label">Status</label>
                    <select id="filterStatus" class="mt-1 h-10 w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-line-strong focus:outline-none focus:ring-4 focus:ring-focus">
                        <option value=''>Todos os status</option>
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-line bg-surface">
        <div class="flex items-center justify-between gap-3 px-5 py-4">
            <h2 class="text-sm font-semibold text-ink">Usuários</h2>
            <span id="totalVisible" class="text-sm text-muted"><?= $totalUsers ?> usuário(s)</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line">
                <thead class="bg-page">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Usuário</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Email</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Perfil</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Cadastrado em</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-muted">Ações</th>
                    </tr>
                </thead>
                <tbody id="userTableBody" class="divide-y divide-line bg-surface">
                    <?php if (empty($users)) : ?>
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-muted">
                                Nenhum usuário encontrado.
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($users as $user) : ?>
                            <tr class="hover:bg-page"
                                data-nome="<?= strtolower(htmlspecialchars($user->getNome())) ?>"
                                data-email="<?= strtolower(htmlspecialchars($user->getEmail())) ?>"
                                data-role="<?= $user->getPerfil()->value ?>"
                                data-status="<?= $user->getStatus()->value ?>">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-surface-muted text-sm font-semibold text-ink-soft">
                                            <?= htmlspecialchars(strtoupper(mb_substr($user->getNome(), 0, 1))) ?>
                                        </div>
                                        <span class="text-sm font-semibold text-ink"><?= htmlspecialchars($user->getNome()) ?></span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-muted"><?= htmlspecialchars($user->getEmail()) ?></td>
                                <td class="px-5 py-4">
                                    <?php if ($user->isAdmin()) : ?>
                                        <span class="inline-flex items-center gap-2 rounded-full bg-warning-soft px-2.5 py-1 text-xs font-semibold text-warning-strong">
                                            <i class="fa-solid fa-shield-halved"></i>
                                            Admin
                                        </span>
                                    <?php else : ?>
                                        <span class="inline-flex items-center gap-2 rounded-full bg-surface-muted px-2.5 py-1 text-xs font-semibold text-ink-soft">
                                            <i class="fa-solid fa-user"></i>
                                            Usuário
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4">
                                    <?php if ($user->getStatus() === Status::ATIVO) : ?>
                                        <span class="inline-flex items-center gap-2 rounded-full bg-success-soft px-2.5 py-1 text-xs font-semibold text-success-strong">
                                            <i class="fa-solid fa-circle text-[0.45rem]"></i>
                                            <?= $user->getStatus()->toText() ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="inline-flex items-center gap-2 rounded-full bg-danger-soft px-2.5 py-1 text-xs font-semibold text-danger-strong">
                                            <i class="fa-solid fa-circle text-[0.45rem]"></i>
                                            <?= $user->getStatus()->toText() ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 text-sm text-muted"><?= $user->getCreatedAt() ? $user->getCreatedAt()->format('d/m/Y') : '-' ?></td>
                                <td class="px-5 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="<?= Url::path('/admin/detalhes/' . $user->getId()) ?>" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-line bg-surface text-ink-soft hover:bg-page" title="Ver detalhes">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="<?= Url::path('/admin/editar/' . $user->getId()) ?>" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-line bg-surface text-ink-soft hover:bg-page" title="Editar usuário">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <button type="button" class="js-btn-excluir inline-flex h-9 w-9 items-center justify-center rounded-lg bg-danger text-white hover:bg-danger-strong" title="Deletar usuário" data-id="<?= htmlspecialchars($user->getId()) ?>" data-nome="<?= htmlspecialchars($user->getNome()) ?>" data-url="<?= Url::path('/admin/deletar') ?>" data-campo="user_id">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type="module" src="<?= Url::path('/js/admin.js') ?>"></script>
