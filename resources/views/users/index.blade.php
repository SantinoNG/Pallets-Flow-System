<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-100 leading-tight">
            {{ __('Gestión de Usuarios del Sistema') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg p-6 border-b border-gray-200 dark:border-zinc-800">
                
                <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-zinc-100">Listado de Usuarios</h3>

                    <div class="flex items-center gap-2">
                        {{-- Exportar a PDF. La ruta users.pdf ya existia, pero no
                             habia ningun boton que llegara a ella. --}}
                        <a href="{{ route('users.pdf') }}"
class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-3 py-1.5 rounded-md transition flex items-center gap-1.5 shadow-sm">
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20">
                                <path d="M4 18h12v-2H4v2zM12 2H8v6H5l5 5 5-5h-3V2z"/>
                            </svg>
                            Exportar PDF
                        </a>

                        {{-- Alta de usuario. Sin esto el alta de personal
                             quedaba en banda muerta: el formulario de
                             empleados solo ofrece usuarios que no sean
                             ni empleados ni clientes. --}}
                        <a href="{{ route('users.create') }}"
                           class="bg-hazard-600 hover:bg-hazard-700 text-white text-xs font-semibold px-3 py-1.5 rounded-md transition flex items-center gap-1.5 shadow-sm">
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20">
                                <path d="M9 4h2v4h4v2h-4v4H9v-4H5V8h4V4z"/>
                            </svg>
                            Nuevo usuario
                        </a>
                    </div>
                </div>

                {{--
                    Filtros. El listado mezcla personal y clientes, asi que
                    se puede separar por tipo y por rol.
                --}}
                <form method="GET" action="{{ route('users.index') }}"
                      class="bg-gray-50 dark:bg-zinc-950 rounded-lg p-4 mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">

                    <div>
                        <label for="tipo" class="block text-xs font-medium text-gray-600 dark:text-zinc-300 mb-1">Tipo de usuario</label>
                        <select id="tipo" name="tipo"
                                class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm">
                            <option value="">Todos</option>
                            <option value="personal"  @selected(request('tipo') === 'personal')>Solo personal</option>
                            <option value="clientes"  @selected(request('tipo') === 'clientes')>Solo clientes</option>
                        </select>
                    </div>

                    <div>
                        <label for="id_role" class="block text-xs font-medium text-gray-600 dark:text-zinc-300 mb-1">Rol</label>
                        <select id="id_role" name="id_role"
                                class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm">
                            <option value="">Todos los roles</option>
                            @foreach($roles as $rol)
                                <option value="{{ $rol->id_role }}" @selected((string) request('id_role') === (string) $rol->id_role)>
                                    {{ $rol->role_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="search" class="block text-xs font-medium text-gray-600 dark:text-zinc-300 mb-1">Buscar</label>
                        <input type="text" id="search" name="search" value="{{ request('search') }}"
                               placeholder="Nombre o email..."
                               class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm">
                    </div>

                    <div class="flex gap-2">
                        <button type="submit"
                                class="flex-1 bg-hazard-600 hover:bg-hazard-700 text-white text-sm font-medium px-3 py-2 rounded-md transition">
                            Filtrar
                        </button>
                        <a href="{{ route('users.index') }}"
                           class="flex-1 text-center bg-gray-200 dark:bg-zinc-800 hover:bg-gray-300 text-gray-700 dark:text-zinc-200 text-sm font-medium px-3 py-2 rounded-md transition">
                            Limpiar
                        </a>
                    </div>
                </form>

                <div class="overflow-x-auto shadow-md sm:rounded-lg">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-zinc-400">
                        <thead class="text-xs text-gray-700 dark:text-zinc-200 uppercase bg-gray-50 dark:bg-zinc-950">
                            <tr>
                                <th scope="col" class="px-6 py-3">Nombre</th>
                                <th scope="col" class="px-6 py-3">Email</th>
                                <th scope="col" class="px-6 py-3">Roles Actuales</th>
                                <th scope="col" class="px-6 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr class="bg-white dark:bg-zinc-900 border-b hover:bg-gray-50 dark:hover:bg-zinc-800">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-zinc-100 whitespace-nowrap">
                                    {{ $user->name }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $user->email }}
                                </td>
                                <td class="px-6 py-4">
                                    @forelse($user->roles as $role)
                                        <span class="bg-hazard-100 text-hazard-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                            {{ $role->name }}
                                        </span>
                                    @empty
                                        <span class="text-red-500 text-xs italic">Sin rol asignado</span>
                                    @endforelse
                                </td>
                                <td class="px-6 py-4 text-center font-mono tabular-nums">
                                <a href="{{ route('users.roles.edit', $user->id_user) }}" 
                                     class="font-medium text-hazard-600 hover:text-hazard-900 bg-hazard-50 px-3 py-2 rounded-md transition">
                                     Gestionar Roles
                                 </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>