/**
 * Botão "ver senha" (o olho). Única implementação: todas as telas usam esta.
 *
 * Marcação esperada (o CSS do olho está em .password-field / .toggle-password, em css/input.css):
 *   <div class="password-field">
 *     <input type="password" id="senha" class="field-input">
 *     <button type="button" class="toggle-password" data-target="senha" aria-label="Mostrar senha">
 *       <i class="fa-solid fa-eye-slash"></i>
 *     </button>
 *   </div>
 *
 * O ícone mostra o ESTADO atual: senha oculta = eye-slash; senha visível = eye.
 */
export function alternarVisibilidadeDaSenha(botao) {
    const campo = document.getElementById(botao.dataset.target);
    if (!campo) return;

    const mostrar = campo.type === 'password';
    campo.type = mostrar ? 'text' : 'password';
    botao.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');

    const icone = botao.querySelector('i');
    if (icone) {
        icone.classList.toggle('fa-eye', mostrar);
        icone.classList.toggle('fa-eye-slash', !mostrar);
    }
}

export function iniciarBotoesDeVerSenha(raiz = document) {
    raiz.querySelectorAll('.toggle-password').forEach((botao) => {
        botao.addEventListener('click', () => alternarVisibilidadeDaSenha(botao));
    });
}
