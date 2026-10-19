<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id_employee ?? $this->route('employee');

        return [
            'id_user'    => ['required', 'exists:users,id_user', 'unique:employees,id_user,' . $employeeId . ',id_employee'],
            'id_role'    => ['required', 'exists:roles,id_role'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_user.required'    => 'Debe seleccionar un usuario válido.',
            'id_user.unique'      => 'El usuario ya pertenece a otro registro de empleado.',
            'id_role.required'    => 'Debe seleccionar un rol o cargo.',
            'first_name.required' => 'El nombre es obligatorio.',
            'last_name.required'  => 'El apellido es obligatorio.',
        ];
    }
}