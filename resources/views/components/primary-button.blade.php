{{--
    Boton principal: la accion(primary) de cada pantalla.

    Usa el naranja de seguridad (hazard) como color de marca, igual que el
    item activo del sidebar. Antes era bg-gray-800, un gris oscuro que en
    modo oscuro quedaba pegado al fondo de las tarjetas.
--}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-hazard-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-hazard-500 focus:bg-hazard-700 active:bg-hazard-800 focus:outline-none focus:ring-2 focus:ring-hazard-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
