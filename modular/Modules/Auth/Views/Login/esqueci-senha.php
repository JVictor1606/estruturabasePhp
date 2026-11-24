<div class="flex min-h-screen w-full flex-col-reverse lg:flex-row-reverse">
    <div class="flex flex-1 flex-col items-center justify-center bg-page px-4 py-10 sm:px-6">
        <div class="w-full max-w-sm">
            <?php if (!empty($emailVerificado)) : ?>
                <form method="POST" action="{{formActionNovaSenha}}" class="form auth-card">
                    <div>
                        <h1 class="auth-title">Recuperar Senha</h1>
                        <p class="auth-subtitle">Crie sua nova senha</p>
                    </div>
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($emailVerificado); ?>">

                    <div>
                        <label class="field-label">Email verificado</label>
                        <div class="mt-1 flex items-center gap-2 rounded-xl border border-success-line bg-success-faint px-3 py-2 text-sm font-medium text-success-strong">
                            <i class="fa-solid fa-circle-check"></i>
                            <?php echo htmlspecialchars($emailVerificado); ?>
                        </div>
                    </div>

                    <div>
                        <label for="nova_senha" class="field-label">Nova Senha</label>
                        <div class="password-field mt-1">
                            <i class="fa-solid fa-lock pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-subtle"></i>
                            <input
                                type="password"
                                id="nova_senha"
                                name="nova_senha"
                                placeholder="Digite a nova senha"
                                minlength="8"
                                required
                                class="field-input field-input--lg field-input--icon">
                            <button type="button" class="toggle-password" data-target="nova_senha" aria-label="Mostrar senha">
                                <i class="fa-solid fa-eye-slash"></i>
                            </button>
                        </div>
                        <ul class="password-requirements mt-3 flex-col gap-1" id="obrigatoriaSenha" style="display:none;"></ul>
                        <?php $senhaErr = old_error('nova_senha'); ?>
                        <?php if ($senhaErr !== '') { ?>
                            <span class="field-error" id="nova_senha-error"><?php echo htmlspecialchars($senhaErr); ?></span>
                        <?php } ?>
                    </div>

                    <div>
                        <label for="confirmar_senha" class="field-label">Confirmar Nova Senha</label>
                        <div class="password-field mt-1">
                            <i class="fa-solid fa-lock pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-subtle"></i>
                            <input
                                type="password"
                                id="confirmar_senha"
                                name="confirmar_senha"
                                placeholder="Confirme a nova senha"
                                minlength="8"
                                required
                                class="field-input field-input--lg field-input--icon">
                            <button type="button" class="toggle-password" data-target="confirmar_senha" aria-label="Mostrar senha">
                                <i class="fa-solid fa-eye-slash"></i>
                            </button>
                        </div>
                    </div>
                    <?= csrf() ?>

                    <button type="submit" class="btn btn-primary btn-lg w-full">
                        <i class="fa-solid fa-save"></i>
                        Salvar Nova Senha
                    </button>

                    <div class="flex justify-center">
                        <a href="{{urlLogin}}" class="btn btn-secondary w-full">
                            <i class="fa-solid fa-arrow-left"></i>
                            Voltar ao login
                        </a>
                    </div>
                </form>

            <?php elseif (!empty($codigoEnviado)) : ?>
                <form method="POST" action="{{formActionVerificarCodigo}}" class="form auth-card" id="formCodigo">
                    <div>
                        <h1 class="auth-title">Recuperar Senha</h1>
                        <p class="auth-subtitle">Enviamos um código de verificação para</p>
                    </div>
                    <div class="flex items-center justify-center gap-2 rounded-xl border border-line bg-surface-muted px-3 py-2 text-sm font-medium text-ink-soft">
                        <i class="fa-solid fa-envelope"></i>
                        <?php echo htmlspecialchars($emailParaCodigo ?? ''); ?>
                    </div>
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($emailParaCodigo ?? ''); ?>">
                    <input type="hidden" name="codigo" id="codigoCompleto">

                    <div>
                        <label class="field-label">Digite o código de 6 dígitos</label>
                        <div class="mt-2 flex items-center justify-center gap-2">
                            <input type="text" class="code-input" maxlength="1" data-index="0" inputmode="numeric" data-mask="number" autocomplete="one-time-code" required>
                            <input type="text" class="code-input" maxlength="1" data-index="1" inputmode="numeric" data-mask="number" required>
                            <input type="text" class="code-input" maxlength="1" data-index="2" inputmode="numeric" data-mask="number" required>
                            <span class="text-xl font-light text-subtle">-</span>
                            <input type="text" class="code-input" maxlength="1" data-index="3" inputmode="numeric" data-mask="number" required>
                            <input type="text" class="code-input" maxlength="1" data-index="4" inputmode="numeric" data-mask="number" required>
                            <input type="text" class="code-input" maxlength="1" data-index="5" inputmode="numeric" data-mask="number" required>
                        </div>
                        <p class="mt-3 text-center text-xs text-faint">
                            <i class="fa-solid fa-circle-info mr-1"></i>
                            Verifique sua caixa de entrada e spam
                        </p>
                    </div>
                    <?php $codErr = old_error('codigo'); ?>
                    <?php if ($codErr !== '') { ?>
                        <span class="mt-2 block text-center text-sm font-medium text-danger" id="codigo-error"><?php echo htmlspecialchars($codErr); ?></span>
                    <?php } ?>
                    <div class="code-error hidden items-center justify-center gap-2 rounded-xl border border-danger-line bg-danger-faint px-4 py-2.5 text-sm text-danger-strong" id="codeError" style="display: none;">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>Código inválido. Tente novamente.</span>
                    </div>

                    <?= csrf() ?>
                    <button type="submit" id="btnVerificarCodigo" disabled class="btn btn-primary btn-lg w-full">
                        <i class="fa-solid fa-shield-check"></i>
                        Verificar Código
                    </button>

                    <div class="code-resend flex flex-col items-center gap-1 pt-1" data-expira="<?php echo $timerExpira ?? 0; ?>">
                        <p class="text-sm text-faint">Não recebeu o código?</p>
                        <button type="button" id="btnReenviar" class="rounded-lg px-3 py-1.5 text-sm font-semibold text-ink hover:bg-surface-muted disabled:cursor-not-allowed disabled:text-subtle disabled:hover:bg-transparent" disabled>
                            Reenviar código <span id="timerReenviar"></span>
                        </button>
                    </div>

                    <div class="flex justify-center">
                        <a href="{{urlEsqueciSenha}}" class="btn btn-secondary w-full">
                            <i class="fa-solid fa-arrow-left"></i>
                            Voltar
                        </a>
                    </div>
                </form>

            <?php else : ?>
                <form method="POST" action="{{formActionVerificar}}" class="form auth-card">
                    <div>
                        <h1 class="auth-title">Recuperar Senha</h1>
                        <p class="auth-subtitle">Digite seu email para redefinir sua senha</p>
                    </div>
                    <div>
                        <label for="email" class="field-label">Email da conta</label>
                        <div class="relative mt-1">
                            <i class="fa-solid fa-envelope pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-subtle"></i>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="seu@email.com"
                                value="<?= old('email') ?>"
                                minlength="5"
                                maxlength="255"
                                required
                                class="h-11 w-full rounded-xl border border-line bg-surface pl-10 pr-3 text-sm text-ink placeholder:text-subtle focus:border-line-strong focus:outline-none focus:ring-4 focus:ring-focus">
                        </div>
                        <?php $emailErr = old_error('email'); ?>
                        <?php if ($emailErr !== '') { ?>
                            <span class="field-error" id="email-error"><?php echo htmlspecialchars($emailErr); ?></span>
                        <?php } ?>
                    </div>
                    <?= csrf() ?>

                    <button type="submit" class="btn btn-primary btn-lg w-full">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        Verificar Email
                    </button>

                    <div class="flex justify-center">
                        <a href="{{urlLogin}}" class="btn btn-secondary w-full">
                            <i class="fa-solid fa-arrow-left"></i>
                            Voltar ao login
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="flex min-h-[220px] flex-1 items-center justify-center bg-gradient-to-br from-primary to-primary-soft px-4 py-10 text-center text-on-primary">
        <div>
            <span class="block text-sm tracking-[0.2em] text-on-primary-soft">RECUPERAÇÃO DE</span>
            <h2 class="mt-2 text-3xl font-bold sm:text-4xl">Senha</h2>
            <div class="mx-auto mt-4 h-0.5 w-14 bg-white/40"></div>
            <p class="mt-4 text-sm text-on-primary-soft">Redefina sua senha de acesso</p>
        </div>
    </div>
</div>

<script type="module" src="js/esqueci-senha.js"></script>
