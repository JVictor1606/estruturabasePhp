<?php

declare(strict_types=1);

namespace Modular\Modules\Auth\Http\Requests;

use Modular\Core\Request;

class RedefinirSenhaFormRequest extends Request
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'nova_senha' => ['required', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'O campo email é obrigatório.',
            'email.email' => 'O campo email deve conter um endereço de email válido.',
            'nova_senha.required' => 'O campo nova senha é obrigatório.',
            'nova_senha.min' => 'A nova senha deve conter no mínimo 8 caracteres.',
        ];
    }
}
