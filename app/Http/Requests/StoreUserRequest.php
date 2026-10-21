<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta de usuario del sistema.
 *
 * Un usuario es O empleado O cliente, segun el rol que se le asigne. Por eso
 * el formulario pide datos de ficha solo cuando el rol es Cliente: sin la
 * fila en `customers` el cliente no tendria carrito ni historial de compras.
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $esCliente = $this->rolEsCliente();

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'id_role'  => ['required', 'integer', 'exists:roles,id_role'],

            // Ficha de cliente: solo cuando el rol es Cliente.
            'first_name' => [$esCliente ? 'required' : 'nullable', 'string', 'max:255'],
            'last_name'  => [$esCliente ? 'required' : 'nullable', 'string', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'address'    => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'El nombre es un campo obligatorio.',
            'email.required'     => 'El email es un campo obligatorio.',
            'email.unique'       => 'Ya existe un usuario registrado con ese email.',
            'password.required'  => 'La contraseña es un campo obligatorio.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'id_role.required'   => 'Debe seleccionar un rol.',
            'first_name.required' => 'El nombre del cliente es obligatorio.',
            'last_name.required'  => 'El apellido del cliente es obligatorio.',
        ];
    }

    /**
     * ¿El rol elegido es el de Cliente?
     *
     * Se resuelve por el nombre del rol, no por el id, para que el
     * formulario no dependa de numeros que cambian al sembrar.
     */
    private function rolEsCliente(): bool
    {
        $id = $this->input('id_role');

        if (! $id) {
            return false;
        }

        return \App\Models\Role::where('id_role', $id)->value('role_name') === 'Cliente';
    }
}
