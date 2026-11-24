import {
    construirListadeRequisitosSenha,
    checarRequisitosSenha,
    allRequirementsPass,
    showFieldError,
    hideFieldError,
    validateRequired,
    validationEmail,
} from './utils/util.js';
import { iniciarBotoesDeVerSenha } from './utils/password-toggle.js';

const DOM = {
    form:       document.querySelector('form'),
    nome:       document.getElementById('nome'),
    email:      document.getElementById('email'),
    senha:      document.getElementById('senha'),
    listaSenha: document.getElementById('senhaRequisitos'),
};

// ===========================================
// TOGGLE VISIBILIDADE DA SENHA
// ===========================================

iniciarBotoesDeVerSenha();

// ===========================================
// REQUISITOS DE SENHA (gerado pelo util.js)
// ===========================================

construirListadeRequisitosSenha(DOM.listaSenha);

DOM.senha?.addEventListener('input', () => {
    const senha = DOM.senha.value;
    hideFieldError(DOM.senha);

    if (senha.length > 0) {
        DOM.listaSenha.style.display = 'flex';
        checarRequisitosSenha(DOM.listaSenha, senha);
    } else {
        DOM.listaSenha.style.display = 'none';
    }
});

// ===========================================
// VALIDAÇÃO NO SUBMIT (sem reload)
// ===========================================

DOM.form?.addEventListener('submit', (e) => {
    let valido = true;
    const senha    = DOM.senha?.value ?? '';
    const required = DOM.senha?.hasAttribute('required') ?? false;

    if (!validateRequired(DOM.nome, 'Nome é obrigatório')) valido = false;

    if (DOM.email && !DOM.email.value.trim()) {
        showFieldError(DOM.email, 'Email é obrigatório');
        valido = false;
    }

    if (required && senha.length === 0) {
        showFieldError(DOM.senha, 'Senha é obrigatória');
        valido = false;
    } else if (senha.length > 0 && !allRequirementsPass(senha)) {
        showFieldError(DOM.senha, 'A senha não atende todos os requisitos');
        DOM.listaSenha.style.display = 'flex';
        checarRequisitosSenha(DOM.listaSenha, senha);
        valido = false;
    }

    if (!valido) e.preventDefault();
});

// Limpa erro ao digitar
DOM.nome?.addEventListener('input',  () => hideFieldError(DOM.nome));
DOM.email?.addEventListener('input', () => hideFieldError(DOM.email));
DOM.senha?.addEventListener('input', () => hideFieldError(DOM.senha));
