<?php

declare(strict_types=1);

namespace Modular\Modules\Auth\Http\Requests;

use Modular\Core\Request;

class VerificarEmailFormRequest extends Request
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'O campo email é obrigatório.',
            'email.email' => 'Digite um endereço de email válido.',
        ];
    }
}
