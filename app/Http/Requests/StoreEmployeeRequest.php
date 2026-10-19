<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_user'    => ['required', 'exists:users,id_user', 'unique:employees,id_user'],
            'id_role'    => ['required', 'exists:roles,id_role'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_user.required'    => 'Debe seleccionar un usuario para el empleado.',
            'id_user.unique'      => 'El usuario seleccionado ya está asignado a otro empleado.',
            'id_role.required'    => 'Debe asignar un rol o cargo al empleado.',
            'first_name.required' => 'El nombre es un campo obligatorio.',
            'last_name.required'  => 'El apellido es un campo obligatorio.',
        ];
    }
}