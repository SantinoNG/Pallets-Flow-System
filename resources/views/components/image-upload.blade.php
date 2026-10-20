{{--
    Campo de subida de la foto de un artículo.

    Muestra la imagen actual (si la hay) y permite cambiarla. Al elegir un
    archivo nuevo aparece la vista previa al instante, sin subir nada todavía:
    así se ve la foto antes de guardar.

    Uso:
        <x-image-upload />                        (crear)
        <x-image-upload :current="$url" />        (editar)
--}}
@props(['current' => null])

<div x-data="{
        vistaPrevia: null,
        alElegir(evento) {
            const archivo = evento.target.files[0];
            if (!archivo) { this.vistaPrevia = null; return; }
            const lector = new FileReader();
            lector.onload = (e) => { this.vistaPrevia = e.target.result; };
            lector.readAsDataURL(archivo);
        }
     }">
    <label class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
        Foto del artículo
    </label>

    <div class="flex items-start gap-4">
        {{-- Vista previa: la elegida ahora, o la que ya estaba guardada --}}
        <div class="relative shrink-0">
            <template x-if="vistaPrevia">
                <img :src="vistaPrevia" alt="Vista previa"
                     class="h-24 w-24 object-cover rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-950">
            </template>

            <template x-if="!vistaPrevia && @js($current)">
                <img src="{{ $current }}" alt="Foto actual"
                     class="h-24 w-24 object-cover rounded-md border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-950">
            </template>

            <template x-if="!vistaPrevia && !@js($current)">
                <div class="h-24 w-24 flex items-center justify-center rounded-md border border-dashed border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800">
                    <svg class="w-8 h-8 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0021.75 18.75V5.25A2.25 2.25 0 0019.5 3h-15a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 006 21z"/>
                    </svg>
                </div>
            </template>
        </div>

        <div class="flex-1 min-w-0">
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                   @change="alElegir($event)"
                   class="block w-full text-xs text-gray-600 dark:text-zinc-300
                          file:mr-3 file:rounded-md file:border-0 file:bg-hazard-600
                          file:px-4 file:py-2 file:text-xs file:font-semibold
                          file:uppercase file:tracking-wider file:text-white
                          hover:file:bg-hazard-500
                          focus:outline-none focus:ring-2 focus:ring-hazard-500 focus:ring-offset-2
                          dark:focus:ring-offset-zinc-900
                          cursor-pointer">

            <p class="mt-1.5 text-xs text-gray-500 dark:text-zinc-400">
                JPG, PNG o WebP. Hasta 2 MB. Recomendado: 800 × 800 px, fondo liso.
            </p>

            @error('image')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
