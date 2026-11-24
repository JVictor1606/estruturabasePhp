

import { iniciarBotoesDeVerSenha } from './utils/password-toggle.js';

const DOM = {
    form: document.querySelector('form'),
    btnSubmit: document.querySelector('button[type="submit"]'),
    authError: document.querySelector('#auth-error')
};

function setLoadingState(isLoading) {
    if (!DOM.btnSubmit) return;

    if (isLoading) {

        DOM.btnSubmit.disabled = true;
        DOM.btnSubmit.classList.add('opacity-70', 'cursor-not-allowed', 'pointer-events-none');
        DOM.btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Entrando...';
    } else {
        DOM.btnSubmit.disabled = false;
        DOM.btnSubmit.classList.remove('opacity-70', 'cursor-not-allowed', 'pointer-events-none');
        DOM.btnSubmit.innerHTML = '<i class="fa-solid fa-right-to-bracket"></i> Entrar';
    }
}


function init() {
    
    iniciarBotoesDeVerSenha();

    
    if (DOM.form) {
        DOM.form.addEventListener('submit', () => setLoadingState(true));
    }

    
    if (DOM.authError && DOM.authError.textContent.trim() !== '') {
        DOM.authError.classList.remove('hidden');
    }
}

document.addEventListener('DOMContentLoaded', init);
