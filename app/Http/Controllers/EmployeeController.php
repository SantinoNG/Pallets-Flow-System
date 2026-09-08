<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    // READ — Lista todos los empleados con sus relaciones
    public function index()
    {
        $employees = Employee::with(['user', 'role'])->get();
        return view('employees.index', compact('employees'));
    }

    // CREATE paso 1 — Muestra el formulario con usuarios y roles disponibles
    public function create()
    {
        // Traemos usuarios que todavía no tienen empleado asignado
        $users = User::whereDoesntHave('employee')->get();
        $roles = Role::all();
        return view('employees.create', compact('users', 'roles'));
    }

    // CREATE paso 2 — Valida y guarda
    public function store(Request $request)
    {
        $request->validate([
            'id_user'    => 'required|exists:users,id_user|unique:employees,id_user',
            'id_role'    => 'required|exists:roles,id_role',
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'phone'      => 'nullable|string|max:20',
        ]);

        Employee::create($request->only('id_user', 'id_role', 'first_name', 'last_name', 'phone'));

        return redirect()->route('employees.index')
                         ->with('success', 'Empleado registrado exitosamente.');
    }

    // UPDATE paso 1 — Muestra el formulario con datos actuales
    public function edit(Employee $employee)
    {
        // Al editar incluimos el usuario actual del empleado para que aparezca en el select
        $users = User::whereDoesntHave('employee')
                     ->orWhere('id_user', $employee->id_user)
                     ->get();
        $roles = Role::all();
        return view('employees.edit', compact('employee', 'users', 'roles'));
    }

    // UPDATE paso 2 — Valida y actualiza
    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'id_user'    => 'required|exists:users,id_user|unique:employees,id_user,' . $employee->id_employee . ',id_employee',
            'id_role'    => 'required|exists:roles,id_role',
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'phone'      => 'nullable|string|max:20',
        ]);

        $employee->update($request->only('id_user', 'id_role', 'first_name', 'last_name', 'phone'));

        return redirect()->route('employees.index')
                         ->with('success', 'Empleado actualizado exitosamente.');
    }

    // DELETE — Elimina el empleado
    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('employees.index')
                         ->with('success', 'Empleado eliminado exitosamente.');
    }
}