<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            🗺️ Hoja de Ruta del Desarrollador
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <div class="bg-white p-6 rounded-lg shadow border-l-4 border-indigo-500">
                <p class="text-gray-700">
                    <strong>¡Bienvenido al sistema base!</strong> Utiliza esta hoja de ruta como tu machete personal para recordar la arquitectura, los comandos y el flujo de trabajo de Laravel.
                </p>
            </div>

            <div>
                <h3 class="text-2xl font-extrabold text-gray-900 mb-4">1. Arquitectura del Proyecto</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500 hover:shadow-md transition">
                        <h4 class="font-bold text-lg mb-2 text-blue-700">El Modelo (Models)</h4>
                        <p class="text-gray-600 text-sm mb-4">Ubicación: <code class="bg-gray-100 p-1 text-red-600 rounded">app/Models/</code></p>
                        <p class="text-sm">Representa la tabla de la base de datos. Aquí definimos las relaciones y los campos que se pueden cargar masivamente (fillable).</p>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500 hover:shadow-md transition">
                        <h4 class="font-bold text-lg mb-2 text-green-700">El Controlador (Controllers)</h4>
                        <p class="text-gray-600 text-sm mb-4">Ubicación: <code class="bg-gray-100 p-1 text-red-600 rounded">app/Http/Controllers/</code></p>
                        <p class="text-sm">Contiene la lógica. Recibe la petición, pide datos al modelo y devuelve una respuesta (una vista).</p>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-purple-500 hover:shadow-md transition">
                        <h4 class="font-bold text-lg mb-2 text-purple-700">La Vista (Views + Blade)</h4>
                        <p class="text-gray-600 text-sm mb-4">Ubicación: <code class="bg-gray-100 p-1 text-red-600 rounded">resources/views/</code></p>
                        <p class="text-sm">La interfaz de usuario. Utilizamos <strong>Blade</strong> para la lógica de visualización y <strong>Tailwind CSS</strong> para el diseño.</p>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow border-l-4 border-red-500 hover:shadow-md transition">
                        <h4 class="font-bold text-lg mb-2 text-red-700">Las Rutas (Routes)</h4>
                        <p class="text-gray-600 text-sm mb-4">Ubicación: <code class="bg-gray-100 p-1 text-red-600 rounded">routes/web.php</code></p>
                        <p class="text-sm">Define las URLs del sistema y las protege usando middlewares de autenticación o roles.</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-900 rounded-xl shadow-xl overflow-hidden border border-gray-700">
                <div class="bg-gray-800 px-6 py-3 border-b border-gray-700 flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    <span class="ml-2 text-gray-400 text-sm font-mono">Terminal - Comandos Esenciales Artisan</span>
                </div>
                <div class="p-6 text-gray-300 font-mono text-sm space-y-6">
                    <div>
                        <p class="text-green-400 mb-1">// 1. Crear TODO el ecosistema de una vez para una nueva tabla</p>
                        <div class="bg-black p-3 rounded">
                            <span class="text-blue-400">php artisan</span> make:model <span class="text-yellow-300">Estudiante</span> -a
                        </div>
                        <p class="text-gray-500 text-xs mt-1">La bandera "-a" (all) genera Modelo, Migración, Controlador, Requests, Seeder y Factory. ¡Un ABM completo en un segundo!</p>
                    </div>

                    <div>
                        <p class="text-green-400 mb-1">// 2. Registrar la ruta del controlador en web.php</p>
                        <div class="bg-black p-3 rounded">
                            Route::resource(<span class="text-yellow-300">'estudiantes'</span>, EstudianteController::class);
                        </div>
                    </div>

                    <div>
                        <p class="text-green-400 mb-1">// 3. Compilar el diseño (Tailwind) mientras trabajas</p>
                        <div class="bg-black p-3 rounded">
                            npm run dev
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-indigo-50 border border-indigo-200 rounded-xl shadow-lg overflow-hidden">
                <div class="bg-indigo-600 px-6 py-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <span>🔐</span> Control de Accesos (Spatie)
                    </h3>
                </div>
                <div class="p-6 text-gray-800 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white p-5 rounded border border-gray-200 shadow-sm">
                            <h4 class="font-bold text-lg text-indigo-700 border-b pb-2 mb-3">✅ Permisos (@@can) - RECOMENDADO</h4>
                            <p class="text-sm text-gray-600 mb-3">Evalúa <strong>qué acción</strong> puede hacer el usuario. Ideal para mostrar/ocultar botones de Editar o Eliminar.</p>
                            <div class="bg-gray-800 p-3 rounded text-sm font-mono text-gray-300">
                                <span class="text-pink-400">@@can</span>(<span class="text-yellow-300">'editar_usuarios'</span>)<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;&lt;button&gt;Editar&lt;/button&gt;<br>
                                <span class="text-pink-400">@@endcan</span>
                            </div>
                        </div>

                        <div class="bg-white p-5 rounded border border-gray-200 shadow-sm">
                            <h4 class="font-bold text-lg text-indigo-700 border-b pb-2 mb-3">👔 Roles (@@role)</h4>
                            <p class="text-sm text-gray-600 mb-3">Evalúa <strong>quién</strong> es el usuario. Útil para mostrar menús enteros según el cargo.</p>
                            <div class="bg-gray-800 p-3 rounded text-sm font-mono text-gray-300">
                                <span class="text-pink-400">@@hasanyrole</span>(<span class="text-yellow-300">'Administrador|Director'</span>)<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;&lt;a href="/panel"&gt;Panel&lt;/a&gt;<br>
                                <span class="text-pink-400">@@endhasanyrole</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden mt-8">
                <div class="bg-gray-800 px-6 py-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <span>🪄</span> La Magia de Route::resource()
                    </h3>
                </div>
                <div class="p-6 text-gray-800">
                    <p class="mb-4 text-sm font-semibold text-gray-700">Al escribir una sola línea, Laravel crea estas 7 rutas automáticamente por nosotros:</p>
                    <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
                        <table class="min-w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-700 uppercase text-xs font-bold border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3">Petición</th>
                                    <th class="px-4 py-3">URL</th>
                                    <th class="px-2 py-3 text-center text-gray-400"></th>
                                    <th class="px-4 py-3">Controlador</th>
                                    <th class="px-4 py-3">Acción (Qué hace)</th>
                                </tr>
                            </thead>
                            <tbody class="font-mono text-xs divide-y divide-gray-100">
                                <tr class="hover:bg-gray-50"><td class="px-4 py-3 text-green-600 font-bold">GET</td><td class="px-4 py-3 text-gray-600">/estudiantes</td><td class="px-2 py-3 text-center">➡️</td><td class="px-4 py-3 text-blue-600 font-bold">index()</td><td class="px-4 py-3 font-sans text-gray-500">Muestra la tabla principal.</td></tr>
                                <tr class="hover:bg-gray-50"><td class="px-4 py-3 text-green-600 font-bold">GET</td><td class="px-4 py-3 text-gray-600">/estudiantes/create</td><td class="px-2 py-3 text-center">➡️</td><td class="px-4 py-3 text-blue-600 font-bold">create()</td><td class="px-4 py-3 font-sans text-gray-500">Formulario para crear.</td></tr>
                                <tr class="hover:bg-gray-50"><td class="px-4 py-3 text-yellow-600 font-bold">POST</td><td class="px-4 py-3 text-gray-600">/estudiantes</td><td class="px-2 py-3 text-center">➡️</td><td class="px-4 py-3 text-blue-600 font-bold">store()</td><td class="px-4 py-3 font-sans text-gray-500">Guarda en la Base de Datos.</td></tr>
                                <tr class="hover:bg-gray-50"><td class="px-4 py-3 text-green-600 font-bold">GET</td><td class="px-4 py-3 text-gray-600">/estudiantes/{id}</td><td class="px-2 py-3 text-center">➡️</td><td class="px-4 py-3 text-blue-600 font-bold">show()</td><td class="px-4 py-3 font-sans text-gray-500">Muestra un solo registro.</td></tr>
                                <tr class="hover:bg-gray-50"><td class="px-4 py-3 text-green-600 font-bold">GET</td><td class="px-4 py-3 text-gray-600">/estudiantes/{id}/edit</td><td class="px-2 py-3 text-center">➡️</td><td class="px-4 py-3 text-blue-600 font-bold">edit()</td><td class="px-4 py-3 font-sans text-gray-500">Formulario para editar.</td></tr>
                                <tr class="hover:bg-gray-50"><td class="px-4 py-3 text-purple-600 font-bold">PUT/PATCH</td><td class="px-4 py-3 text-gray-600">/estudiantes/{id}</td><td class="px-2 py-3 text-center">➡️</td><td class="px-4 py-3 text-blue-600 font-bold">update()</td><td class="px-4 py-3 font-sans text-gray-500">Actualiza en la Base de Datos.</td></tr>
                                <tr class="hover:bg-gray-50"><td class="px-4 py-3 text-red-600 font-bold">DELETE</td><td class="px-4 py-3 text-gray-600">/estudiantes/{id}</td><td class="px-2 py-3 text-center">➡️</td><td class="px-4 py-3 text-blue-600 font-bold">destroy()</td><td class="px-4 py-3 font-sans text-gray-500">Elimina el registro.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bg-emerald-50 border border-emerald-200 rounded-xl shadow-lg overflow-hidden mt-8">
                <div class="bg-emerald-600 px-6 py-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <span>🗄️</span> Base de Datos: Migraciones y Seeders
                    </h3>
                </div>
                <div class="p-6 text-gray-800 space-y-6">
                    <p class="text-sm">
                        Las <strong>Migraciones</strong> son el "control de versiones" de las tablas (crean las columnas). Los <strong>Seeders</strong> son scripts automáticos para "sembrar" (llenar) esas tablas con datos iniciales (ej: el usuario Administrador o categorías por defecto).
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white p-5 rounded border border-gray-200 shadow-sm">
                            <h4 class="font-bold text-gray-700 mb-2">Paso 1: Crear el Seeder</h4>
                            <p class="text-xs text-gray-600 mb-3">Genera un archivo en <code>database/seeders/</code></p>
                            <code class="bg-gray-800 text-green-400 px-3 py-2 rounded text-xs font-mono block">php artisan make:seeder RoleSeeder</code>
                        </div>
                        
                        <div class="bg-white p-5 rounded border border-gray-200 shadow-sm">
                            <h4 class="font-bold text-gray-700 mb-2">Paso 2: Registrar el Seeder</h4>
                            <p class="text-xs text-gray-600 mb-3">Abrí <code>DatabaseSeeder.php</code> y agregalo adentro de run():</p>
                            <code class="bg-gray-800 text-blue-300 px-3 py-2 rounded text-xs font-mono block">$this->call([RoleSeeder::class]);</code>
                        </div>
                    </div>

                    <div class="bg-red-50 p-5 rounded border border-red-200 mt-4">
                        <h4 class="font-bold text-red-700 mb-3 flex items-center gap-2">
                            <span>🔥</span> Comandos de Destrucción y Reconstrucción
                        </h4>
                        <p class="text-xs text-gray-600 mb-4">Cuando modificas una migración o un seeder viejo, necesitas resetear la base de datos. <strong>¡Ojo! Estos comandos borran los datos actuales.</strong></p>
                        
                        <ul class="space-y-4 text-sm">
                            <li class="flex flex-col sm:flex-row sm:items-start gap-3">
                                <code class="font-mono text-xs bg-white text-red-600 px-3 py-2 rounded border border-red-200 shadow-sm whitespace-nowrap">php artisan migrate:fresh --seed</code>
                                <div>
                                    <span class="font-bold text-gray-800 block">El más usado en Desarrollo.</span>
                                    <span class="text-gray-600 text-xs">Elimina TODAS las tablas de golpe, las vuelve a crear vacías y ejecuta los Seeders. Es el más rápido y limpio.</span>
                                </div>
                            </li>
                            <li class="flex flex-col sm:flex-row sm:items-start gap-3">
                                <code class="font-mono text-xs bg-white text-orange-600 px-3 py-2 rounded border border-orange-200 shadow-sm whitespace-nowrap">php artisan migrate:refresh --seed</code>
                                <div>
                                    <span class="font-bold text-gray-800 block">El método paso a paso.</span>
                                    <span class="text-gray-600 text-xs">Hace un "rollback" (revierte las migraciones una por una hacia atrás), luego vuelve a migrar hacia adelante y siembra.</span>
                                </div>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>