<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['user', 'role']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('id_role')) {
            $query->where('id_role', $request->id_role);
        }

        $employees = $query->latest('id_employee')->paginate(10)->withQueryString();
        $roles = Role::all();

        return view('employees.index', compact('employees', 'roles'));
    }

    public function create()
    {
        $users = User::whereDoesntHave('employee')->get();
        $roles = Role::all();
        return view('employees.create', compact('users', 'roles'));
    }

    // Reemplazamos $request->validate() por StoreEmployeeRequest
    public function store(StoreEmployeeRequest $request)
    {
        Employee::create($request->validated());

        return redirect()->route('employees.index')
                         ->with('success', 'Empleado registrado exitosamente.');
    }

    public function edit(Employee $employee)
    {
        $users = User::whereDoesntHave('employee')
                     ->orWhere('id_user', $employee->id_user)
                     ->get();
        $roles = Role::all();
        return view('employees.edit', compact('employee', 'users', 'roles'));
    }

    // Reemplazamos $request->validate() por UpdateEmployeeRequest
    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $employee->update($request->validated());

        return redirect()->route('employees.index')
                         ->with('success', 'Empleado actualizado exitosamente.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('employees.index')
                         ->with('success', 'Empleado eliminado exitosamente.');
    }

    public function exportPdf(Request $request)
    {
        $query = Employee::with(['user', 'role']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('id_role')) {
            $query->where('id_role', $request->id_role);
        }

        $employees = $query->latest('id_employee')->get();
        $pdf = Pdf::loadView('employees.pdf', compact('employees'));

        return $pdf->download('reporte_empleados.pdf');
    }
}