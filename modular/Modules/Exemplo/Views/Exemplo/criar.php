<?php

/**
 * @var string $voltarUrl
 * @var string $urlSalvar
 */

?>

<div class="space-y-6">
    <div>
        <a href="<?= $voltarUrl ?>" class="inline-flex items-center gap-2 text-sm text-muted hover:text-ink">
            <i class="fa-solid fa-arrow-left"></i> Voltar
        </a>
        <h1 class="page-title mt-2">Novo Registro</h1>
    </div>

    <div class="rounded-2xl border border-line bg-surface p-6">
        <form method="POST" action="<?= $urlSalvar ?>">
            <?= csrf() ?>

            <div class="space-y-4">
                <div>
                    <label for="titulo" class="field-label">Título <span class="text-danger">*</span></label>
                    <input type="text" id="titulo" name="titulo" required minlength="3" maxlength="150"
                        value="<?= old('titulo') ?>"
                        class="mt-1 block w-full rounded-xl border border-line px-3 py-2 text-sm focus:border-line-strong focus:outline-none focus:ring-4 focus:ring-focus">
                    <?= old_error('titulo') ?>
                </div>

                <div>
                    <label for="descricao" class="field-label">Descrição</label>
                    <textarea id="descricao" name="descricao" maxlength="500" rows="3"
                        class="mt-1 block w-full rounded-xl border border-line px-3 py-2 text-sm focus:border-line-strong focus:outline-none focus:ring-4 focus:ring-focus"
                    ><?= old('descricao') ?></textarea>
                    <?= old_error('descricao') ?>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="<?= $voltarUrl ?>"
                    class="inline-flex items-center gap-2 rounded-xl border border-line px-4 py-2 text-sm font-medium text-ink-soft hover:bg-page">
                    Cancelar
                </a>
                <button type="submit"
                    class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>
