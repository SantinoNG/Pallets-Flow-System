<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-100 leading-tight">
            Asignar Rol a: <span class="text-hazard-600">{{ $user->name }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if(session('success'))
                    <div class="bg-green-100 dark:bg-green-950/40 border border-green-400 dark:border-green-900 text-green-700 dark:text-green-300 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="bg-red-100 dark:bg-red-950/40 border border-red-400 dark:border-red-900 text-red-700 dark:text-red-300 px-4 py-3 rounded relative mb-4" role="alert">
                        <ul class="list-disc pl-5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('users.roles.update', $user->id_user) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <h5 class="mb-4 text-lg font-bold text-gray-700 dark:text-zinc-200">
                        Rol actual de <span class="text-hazard-600">{{ $user->name }}</span>:
                        <span class="text-gray-600 dark:text-zinc-300">{{ $user->role?->role_name ?? 'sin rol asignado' }}</span>
                    </h5>

                    {{--
                        users.id_role es una Foreign Key simple: cada usuario tiene
                        UN solo rol. Por eso esto es un <select> y no un grupo de
                        checkboxes (los checkboxes Allow una relacion N:M, que
                        no es como funciona este proyecto).

                        La columna PK es `id_role` y el nombre está en `role_name`
                        (no `id` / `name`), porque la tabla la definio el profesor
                        a mano, no con el migrador de Spatie.
                    --}}
                    <div class="mb-6">
                        <label for="id_role" class="block font-medium text-gray-700 dark:text-zinc-200 mb-2">
                            Selecciona el rol:
                        </label>
                        <select
                            name="id_role"
                            id="id_role"
                            required
                            class="rounded border-gray-300 dark:border-zinc-700 text-hazard-600 shadow-sm focus:border-hazard-300 focus:ring focus:ring-hazard-200 focus:ring-opacity-50 w-full md:w-1/2"
                        >
                            @foreach($roles as $role)
                                <option
                                    value="{{ $role->id_role }}"
                                    @selected($user->hasRole($role->role_name))
                                >
                                    {{ $role->role_name }}
                                </option>
                            @endforeach
                        </select>

                        @if($roles->isEmpty())
                            <p class="mt-2 text-sm text-red-600">
                                No hay roles cargados. Ejecutá
                                <code class="bg-gray-100 dark:bg-zinc-950 px-1">php artisan db:seed --class=RoleSeeder</code>.
                            </p>
                        @endif
                    </div>

                    <div class="border-t border-gray-200 dark:border-zinc-800 pt-4 flex space-x-3">
                        <x-primary-button>
                            Guardar Cambios
                        </x-primary-button>

                        <a href="{{ route('users.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-zinc-800 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-zinc-200 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-hazard-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Volver
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
