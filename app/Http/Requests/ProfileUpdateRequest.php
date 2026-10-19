<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',

                /*
                 * Se pasa el MODELO entero en vez de ->id a proposito.
                 *
                 * Cuando la clave primaria no se llama "id" (en este proyecto
                 * es `id_user`), pasar el id suelto hace que la regla unique
                 * compare contra la columna equivocada y el usuario que edita
                 * su propio perfil recibe "El campo correo electrónico ya ha
                 * sido registrado".
                 *
                 * Laravel resuelve bien la PK cuando le pasás el modelo.
                 */
                Rule::unique(User::class)->ignore($this->user()),
            ],
        ];
    }
}
