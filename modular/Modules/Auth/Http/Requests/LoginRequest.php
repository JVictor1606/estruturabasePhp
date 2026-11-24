<?php

declare(strict_types=1);

namespace Modular\Modules\Auth\Http\Requests;

use Modular\Core\Request;

class LoginRequest extends Request
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'min:5', 'max:255'],
            'senha' => ['required', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'O campo e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'email.min' => 'O e-mail deve ter no mínimo 5 caracteres.',
            'email.max' => 'O e-mail deve ter no máximo 255 caracteres.',

            'senha.required' => 'O campo senha é obrigatório.',
            'senha.min' => 'A senha deve ter no mínimo 8 caracteres.',
        ];
    }
}
