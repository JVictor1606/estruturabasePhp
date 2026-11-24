<?php

use Src\App\Utils\Url;

$user = $user ?? null;

$criando = $user === null;
$formAction = $criando ? Url::path('/admin/novo') : Url::path('/admin/editar/' . ($user?->getId() ?? ''));
$titulo = $criando ? 'Novo Usuário' : 'Editar Usuário';
$subtitulo = $criando ? 'Preencha os dados para criar um novo usuário' : 'Altere os campos desejados';
$userNome = $user?->getNome() ?? '';
$userEmail = $user?->getEmail() ?? '';
$userIsAdmin = $user?->isAdmin() ?? false;
$userIsAtivo = $user?->isAtivo() ?? false;
$isProprioUsuario = $user !== null && ($_SESSION['user_id'] ?? null) === $user->getId();
?>

<div class="space-y-6">
    <div class="flex flex-col gap-2">
        <a href="<?= Url::path('/admin') ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-soft hover:text-ink">
            <i class="fa-solid fa-arrow-left"></i>
            Voltar para Administração
        </a>
        <div>
            <h1 class="page-title"><?= $titulo ?></h1>
            <p class="mt-1 text-sm text-muted"><?= $subtitulo ?></p>
        </div>
    </div>

    <div class="card">
        <?php if (!$criando && $user !== null) : ?>
            <div class="mb-5 flex items-center gap-3 rounded-2xl border border-line bg-page p-4">
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-surface-muted text-sm font-semibold text-ink-soft">
                    <?= htmlspecialchars(strtoupper(mb_substr($userNome, 0, 1))) ?>
                </div>
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold text-ink"><?= htmlspecialchars($userNome) ?></div>
                    <div class="truncate text-sm text-muted"><?= htmlspecialchars($userEmail) ?></div>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $formAction ?>" class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <?= csrf() ?>
            <?php if (! $criando) : ?>
                <input type="hidden" name="version" value="<?= $user?->getVersion() ?? 1 ?>">
            <?php endif; ?>

            <div class="md:col-span-1">
                <label for="nome" class="field-label">Nome</label>
                <input type="text" id="nome" name="nome" class="mt-1 w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink placeholder:text-subtle focus:border-line-strong focus:outline-none focus:ring-4 focus:ring-focus" value="<?= htmlspecialchars($userNome) ?>" placeholder="Nome completo" required>
                <?php $nomeErr = old_error('nome'); ?>
                <?php if ($nomeErr !== '') { ?>
                    <p class="field-error" id="nome-error"><?php echo htmlspecialchars($nomeErr); ?></p>
                <?php } ?>
            </div>

            <div class="md:col-span-1">
                <label for="email" class="field-label">Email</label>
                <input type="email" id="email" name="email" class="mt-1 w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink placeholder:text-subtle focus:border-line-strong focus:outline-none focus:ring-4 focus:ring-focus" value="<?= htmlspecialchars($userEmail) ?>" placeholder="email@exemplo.com" required>
                <?php $emailErr = old_error('email'); ?>
                <?php if ($emailErr !== '') { ?>
                    <p class="field-error" id="email-error"><?php echo htmlspecialchars($emailErr); ?></p>
                <?php } ?>
            </div>

            <div class="md:col-span-2">
                <div class="flex items-center justify-between gap-2">
                    <label for="senha" class="field-label">
                        <?= $criando ? 'Senha' : 'Nova senha' ?>
                        <?= $criando ? '<span class="text-danger-strong">*</span>' : '' ?>
                    </label>
                    <?php if (! $criando) : ?>
                        <span class="text-sm text-muted">(deixe em branco para manter)</span>
                    <?php endif; ?>
                </div>

                <?php $senhaErr = old_error('senha'); ?>
                <?php if ($senhaErr !== '') { ?>
                    <p class="field-error" id="senha-error"><?php echo htmlspecialchars($senhaErr); ?></p>
                <?php } ?>

                <div class="password-field mt-2">
                    <input type="password" id="senha" name="senha" class="field-input" placeholder="<?= $criando ? 'Mínimo 8 caracteres' : 'Nova senha...' ?>" <?= $criando ? 'required' : '' ?>>
                    <button type="button" class="toggle-password" data-target="senha" title="Ver senha" aria-label="Mostrar senha">
                        <i class="fa-solid fa-eye-slash"></i>
                    </button>
                </div>

                <ul id="senhaRequisitos" class="password-requirements mt-3 flex-col gap-1" style="display:none;"></ul>
            </div>

            <div class="md:col-span-1">
                <label for="perfil" class="field-label">Perfil</label>
                <select id="perfil" name="perfil" class="mt-1 h-10 w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-line-strong focus:outline-none focus:ring-4 focus:ring-focus">
                    <option value="1" <?= $userIsAdmin ? '' : 'selected' ?>>Usuário</option>
                    <option value="2" <?= $userIsAdmin ? 'selected' : '' ?>>Administrador</option>
                </select>
                <?php $perfilErr = old_error('perfil'); ?>
                <?php if ($perfilErr !== '') { ?>
                    <p class="field-error" id="perfil-error"><?php echo htmlspecialchars($perfilErr); ?></p>
                <?php } ?>
            </div>

            <?php if (!$criando) : ?>
                <div class="md:col-span-1">
                    <label for="status" class="field-label">Status</label>
                    <select id="status" name="status" <?= $isProprioUsuario ? 'disabled' : '' ?> class="mt-1 h-10 w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-line-strong focus:outline-none focus:ring-4 focus:ring-focus disabled:cursor-not-allowed disabled:bg-page disabled:text-faint">
                        <option value="0" <?= $userIsAtivo ? '' : 'selected' ?> <?= $isProprioUsuario ? 'disabled' : '' ?>>Inativo</option>
                        <option value="1" <?= $userIsAtivo ? 'selected' : '' ?>>Ativo</option>
                    </select>
                    <?php if ($isProprioUsuario) : ?>
                        <input type="hidden" name="status" value="1">
                        <p class="mt-2 text-xs text-faint">Você não pode desativar a sua própria conta.</p>
                    <?php endif; ?>
                    <?php $statusErr = old_error('status'); ?>
                    <?php if ($statusErr !== '') { ?>
                        <p class="field-error" id="status-error"><?php echo htmlspecialchars($statusErr); ?></p>
                    <?php } ?>
                </div>
            <?php endif; ?>

            <div class="md:col-span-2 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <a href="<?= Url::path('/admin') ?>" class="btn btn-secondary">
                    <i class="fa-solid fa-xmark"></i>
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-<?= $criando ? 'user-plus' : 'check' ?>"></i>
                    <?= $criando ? 'Criar usuário' : 'Salvar alterações' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script type="module" src="<?= Url::path('/js/admin-editar.js') ?>"></script>
