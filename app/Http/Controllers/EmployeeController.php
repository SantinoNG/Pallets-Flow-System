<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Support\ReporteEmpleado;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
        $users = $this->usuariosParaEmpleado();
        $roles = Role::all();
        return view('employees.create', compact('users', 'roles'));
    }

    /**
     * Usuarios que pueden llegar a ser empleados.
     *
     * Un usuario es O empleado O cliente, no las dos cosas: el personal
     * atiende el mostrador y el cliente compra. Por eso el catalogo de
     * personal excluye a quien tiene el rol 'Cliente'.
     *
     * El criterio es el ROL y no "tiene ficha de cliente": son dos cosas
     * distintas. Alguien que compro en la tienda y despues entro a
     * trabajar puede tener las dos, y queda igual de asignado al personal.
     *
     * Antes salia todo usuario sin ficha de `employee`, o sea TODOS los
     * clientes: el formulario de alta de empleados ofrecia a los
     * compradores, con su email. Y en la edicion el `orWhere` iba sin
     * agrupar, asi que el AND/OR se mezclaba.
     */
    private function usuariosParaEmpleado(?Employee $employee = null)
    {
        return User::query()
            ->whereDoesntHave('employee')
            ->esPersonal()
            ->when($employee, function ($q) use ($employee) {
                // Agrupado: sin el parentesis, el OR se aplicaria a todo
                // el WHERE y volverian a entrar los clientes.
                $q->orWhere(function ($q) use ($employee) {
                    $q->where('id_user', $employee->id_user);
                });
            })
            ->orderBy('name')
            ->get();
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
        $users = $this->usuariosParaEmpleado($employee);
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

    /**
     * Reporte interno del empleado, con el contenido de su rol.
     *
     * No es el listado de empleados: es una hoja que se le manda a la
     * persona. Al Depósito le llega el pedido de reposición, al Vendedor
     * el resumen de ventas y disponibilidad, al Encargado el resumen
     * operativo y al Jefe/Administrador la foto general.
     */
    public function report(Request $request, Employee $employee)
    {
        $employee->load(['user', 'role']);

        /*
         * El resumen general (Jefe/Administrador) incluye facturación
         * histórica, clientes y estructura del equipo. El Encargado puede
         * generar los reportes operativos (Depósito, Vendedor, Encargado),
         * pero no el general de un Jefe/Administrador: serían datos que no
         * le corresponden. La ruta ya deja afuera al Vendedor, Depósito y
         * Cliente; acá se cierra el caso del Encargado.
         */
        $rolDestino = $employee->role?->role_name;
        $esGeneral  = in_array($rolDestino, ['Jefe', 'Administrador'], true);

        if ($esGeneral && ! ($request->user()?->isAdmin() ?? false)) {
            abort(403, 'El reporte general es solo para Jefe o Administrador.');
        }

        $datos = ReporteEmpleado::para($employee);

        $pdf = Pdf::loadView('employees.report-pdf', $datos)
            ->setPaper('a4', 'portrait');

        $archivo = 'reporte-'
            . Str::slug($datos['rol']) . '-'
            . Str::slug($employee->first_name . ' ' . $employee->last_name)
            . '.pdf';

        return $pdf->download($archivo);
    }
}
