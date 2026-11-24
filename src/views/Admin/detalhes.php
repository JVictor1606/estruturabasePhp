<?php

use Src\App\Utils\Url;

$user = $user ?? null;

?>

<?php if ($user !== null) : ?>
    <div class="space-y-6">
        <div class="flex flex-col gap-2">
            <a href="<?= Url::path('/admin') ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-soft hover:text-ink">
                <i class="fa-solid fa-arrow-left"></i>
                Voltar para Administração
            </a>
            <div>
                <h1 class="page-title">Detalhes do usuário</h1>
            </div>
        </div>

        <div class="card">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-surface-muted text-base font-semibold text-ink-soft">
                        <?= htmlspecialchars(strtoupper(mb_substr($user->getNome(), 0, 1))) ?>
                    </div>
                    <div class="min-w-0">
                        <div class="truncate text-base font-semibold text-ink"><?= htmlspecialchars($user->getNome()) ?></div>
                        <div class="truncate text-sm text-muted"><?= htmlspecialchars($user->getEmail()) ?></div>
                        <div class="mt-2">
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
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center">
                    <button type="button" class="js-btn-excluir btn btn-danger" data-id="<?= htmlspecialchars($user->getId()) ?>" data-nome="<?= htmlspecialchars($user->getNome()) ?>" data-url="<?= Url::path('/admin/deletar') ?>" data-campo="user_id">
                        <i class="fa-solid fa-trash"></i>
                        Deletar
                    </button>
                    <a href="<?= Url::path('/admin/editar/' . $user->getId()) ?>" class="btn btn-primary">
                        <i class="fa-solid fa-pen-to-square"></i>
                        Editar
                    </a>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-3 md:grid-cols-2">
                <div class="rounded-2xl border border-line p-4">
                    <p class="text-sm font-medium text-muted">ID</p>
                    <p class="mt-1 text-sm font-semibold text-ink"><?= htmlspecialchars($user->getId()) ?></p>
                </div>

                <div class="rounded-2xl border border-line p-4">
                    <p class="text-sm font-medium text-muted">Status</p>
                    <div class="mt-2">
                        <?php if ($user->isAtivo()) : ?>
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
                    </div>
                </div>

                <div class="rounded-2xl border border-line p-4">
                    <p class="text-sm font-medium text-muted">Cadastrado em</p>
                    <p class="mt-1 text-sm font-semibold text-ink"><?= $user->getCreatedAt() ? $user->getCreatedAt()->format('d/m/Y \à\s H:i') : '-' ?></p>
                </div>

                <div class="rounded-2xl border border-line p-4">
                    <p class="text-sm font-medium text-muted">Última atualização</p>
                    <p class="mt-1 text-sm font-semibold text-ink"><?= $user->getUpdatedAt() ? $user->getUpdatedAt()->format('d/m/Y \à\s H:i') : '-' ?></p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
