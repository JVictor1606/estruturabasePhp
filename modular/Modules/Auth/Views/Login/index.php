<div class="flex min-h-screen w-full flex-col-reverse lg:flex-row-reverse">
    <div class="flex flex-1 flex-col items-center justify-center bg-page px-4 py-10 sm:px-6">
        <form method="POST" action="{{formAction}}" autocomplete="on" class="form auth-card w-full max-w-sm">
            <div>
                <h1 class="auth-title">Login</h1>
                <p class="auth-subtitle">Acesse sua conta para continuar</p>
            </div>

            <div>
                <label for="email" class="field-label">Email</label>
                <div class="relative mt-1">
                    <i class="fa-solid fa-envelope pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-subtle"></i>
                    <input type="email" id="email" name="email" placeholder="seu@email.com" value="<?= old('email') ?>" minlength="5" maxlength="255" required
                        class="h-11 w-full rounded-xl border border-line bg-surface pl-10 pr-3 text-sm text-ink placeholder:text-subtle focus:border-line-strong focus:outline-none focus:ring-4 focus:ring-focus">
                </div>
                <?php $emailErr = old_error('email'); ?>
                <?php if ($emailErr !== '') { ?>
                    <span class="field-error" id="email-error"><?php echo htmlspecialchars($emailErr); ?></span>
                <?php } ?>
            </div>

            <div>
                <label for="senha" class="field-label">Senha</label>
                <div class="password-field mt-1">
                    <i class="fa-solid fa-lock pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-subtle"></i>
                    <input type="password" id="senha" name="senha" placeholder="Digite sua senha" minlength="8" required
                        class="field-input field-input--lg field-input--icon">
                    <button type="button" id="btnVerSenha" class="toggle-password" aria-label="Mostrar senha" data-target="senha">
                        <i class="fa-solid fa-eye-slash"></i>
                    </button>
                </div>
                <?php $senhaErr = old_error('senha'); ?>
                <?php if ($senhaErr !== '') { ?>
                    <span class="field-error" id="password-error"><?php echo htmlspecialchars($senhaErr); ?></span>
                <?php } ?>
            </div>

            <div class="error-auth hidden rounded-xl border border-danger-line bg-danger-faint px-4 py-2.5 text-center text-sm text-danger-strong" id="auth-error">{{erro}}</div>
            <?= csrf() ?>

            <button type="submit" class="btn btn-primary btn-lg w-full">
                <i class="fa-solid fa-right-to-bracket"></i>
                Entrar
            </button>

            <div class="flex justify-center pt-1">
                <a href="{{esqueciSenha}}" class="inline-flex items-center gap-2 text-sm text-muted hover:text-ink">
                    <i class="fa-solid fa-key"></i>
                    Esqueci minha senha
                </a>
            </div>
        </form>
    </div>

    <div class="flex min-h-[220px] flex-1 items-center justify-center bg-gradient-to-br from-primary to-primary-soft px-4 py-10 text-center text-on-primary">
        <div>
            <span class="block text-sm tracking-[0.2em] text-on-primary-soft">BEM-VINDO AO</span>
            <h2 class="mt-2 text-3xl font-bold sm:text-4xl">Projeto</h2>
        </div>
    </div>
</div>

<script type="module" src="js/login.js"></script>
