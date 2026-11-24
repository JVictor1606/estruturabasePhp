<?php

use Modular\Modules\Usuarios\Domain\Status;
use Modular\Shared\Support\Url;

$usuario = $usuario ?? null;
$urlAtualizarSenha = $urlAtualizarSenha ?? '';

?>

<?php if ($usuario !== null) : ?>
    <div class="space-y-6">
        <div>
            <h1 class="page-title">Configurações</h1>
            <p class="mt-1 text-sm text-muted">Gerencie sua conta e preferências</p>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            <div class="card xl:col-span-1">
                <div class="flex items-center gap-3">
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-surface-muted text-base font-semibold text-ink-soft">
                        <?= htmlspecialchars(strtoupper(mb_substr($usuario->getNome(), 0, 1))) ?>
                    </div>
                    <div class="min-w-0">
                        <div class="truncate text-base font-semibold text-ink"><?= htmlspecialchars($usuario->getNome()) ?></div>
                        <div class="truncate text-sm text-muted"><?= htmlspecialchars($usuario->getEmail()) ?></div>
                        <div class="mt-2">
                            <?php if ($usuario->isAdmin()) : ?>
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

                <div class="mt-6 space-y-3">
                    <div class="rounded-2xl border border-line p-4">
                        <p class="text-sm font-medium text-muted">ID</p>
                        <p class="mt-1 text-sm font-semibold text-ink">#<?= (string) $usuario->getId() ?></p>
                    </div>
                    <div class="rounded-2xl border border-line p-4">
                        <p class="text-sm font-medium text-muted">Status</p>
                        <div class="mt-2">
                            <?php if ($usuario->isAtivo()) : ?>
                                <span class="inline-flex items-center gap-2 rounded-full bg-success-soft px-2.5 py-1 text-xs font-semibold text-success-strong">
                                    <i class="fa-solid fa-circle text-[0.45rem]"></i>
                                    <?= $usuario->getStatus()->toText() ?>
                                </span>
                            <?php else : ?>
                                <span class="inline-flex items-center gap-2 rounded-full bg-danger-soft px-2.5 py-1 text-xs font-semibold text-danger-strong">
                                    <i class="fa-solid fa-circle text-[0.45rem]"></i>
                                    <?= $usuario->getStatus()->toText() ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-line p-4">
                        <p class="text-sm font-medium text-muted">Membro desde</p>
                        <p class="mt-1 text-sm font-semibold text-ink"><?= $usuario->getCreatedAt() ? $usuario->getCreatedAt()->format('d/m/Y') : '-' ?></p>
                    </div>
                </div>
            </div>

            <div class="card xl:col-span-2">
                <div class="flex items-start gap-3">
                    <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-surface-muted text-ink-soft">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-ink">Alterar senha</h2>
                        <p class="mt-1 text-sm text-muted">Atualize sua senha de acesso</p>
                    </div>
                </div>

                <form method="POST" action="<?= htmlspecialchars($urlAtualizarSenha) ?>" data-no-client-validation="true"
                    id="form-alterar-senha" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <?= csrf() ?>

                    <div class="md:col-span-1">
                        <label for="senha_atual" class="field-label">Senha atual</label>
                        <div class="password-field mt-1">
                            <input type="password" id="senha_atual" name="senha_atual" class="field-input" placeholder="Digite sua senha atual" required>
                            <button type="button" class="toggle-password" data-target="senha_atual" title="Ver senha" aria-label="Mostrar senha">
                                <i class="fa-solid fa-eye-slash"></i>
                            </button>
                        </div>
                        <?php $senhaAtualErr = old_error('senha_atual'); ?>
                        <?php if ($senhaAtualErr !== '') { ?>
                            <p class="field-error" id="senha-error"><?php echo htmlspecialchars($senhaAtualErr); ?></p>
                        <?php } ?>
                    </div>

                    <div class="md:col-span-1">
                        <label for="nova_senha" class="field-label">Nova senha</label>
                        <div class="password-field mt-1">
                            <input type="password" id="nova_senha" name="nova_senha" class="field-input" placeholder="Mínimo 8 caracteres" minlength="8" required>
                            <button type="button" class="toggle-password" data-target="nova_senha" title="Ver senha" aria-label="Mostrar senha">
                                <i class="fa-solid fa-eye-slash"></i>
                            </button>
                        </div>
                        <?php $novaSenhaErr = old_error('nova_senha'); ?>
                        <?php if ($novaSenhaErr !== '') { ?>
                            <p class="field-error" id="nova-senha-error"><?php echo htmlspecialchars($novaSenhaErr); ?></p>
                        <?php } ?>
                        <ul id="senhaRequisitos" class="password-requirements mt-3 flex-col gap-1" style="display:none;"></ul>
                    </div>

                    <div class="md:col-span-2">
                        <label for="confirmar_senha" class="field-label">Confirmar nova senha</label>
                        <div class="password-field mt-1">
                            <input type="password" id="confirmar_senha" name="confirmar_senha" class="field-input" placeholder="Repita a nova senha" minlength="8" required>
                            <button type="button" class="toggle-password" data-target="confirmar_senha" title="Ver senha" aria-label="Mostrar senha">
                                <i class="fa-solid fa-eye-slash"></i>
                            </button>
                        </div>
                        <?php $confirmarSenhaErr = old_error('confirmar_senha'); ?>
                        <?php if ($confirmarSenhaErr !== '') { ?>
                            <p class="field-error" id="confirmar-senha-error"><?php echo htmlspecialchars($confirmarSenhaErr); ?></p>
                        <?php } ?>

                    </div>

                    <div class="md:col-span-2 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="reset" class="btn btn-secondary">
                            <i class="fa-solid fa-rotate-left"></i>
                            Limpar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-key"></i>
                            Salvar nova senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script type="module" src="<?= Url::path('/js/config.js') ?>"></script>
