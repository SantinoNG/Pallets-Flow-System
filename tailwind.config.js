import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Tailwind v3 (la version que corre el build de verdad).
 *
 * Paleta industrial: superficies de grafito (zinc, gris neutro y frio, sin
 * el tinte azul de slate) y un unico color de acento, el naranja de
 * seguridad de las senalizacion industrial.
 *
 * El acento se llama `hazard` a proposito: es el nombre del color de las
 * marcas de seguridad y deja claro en el codigo cual es el color de marca
 * de la aplicacion. Se cambia en un solo lugar (este archivo) o con un
 * grep sobre `hazard-`.
 *
 * Modo oscuro: `darkMode: 'class'` para que el interruptor de la interfaz
 * lo controle y no el sistema operativo.
 */
export default {
    content: [
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        // Las vistas de paginacion del vendor ya NO se escanean: usan la
        // paleta azul de Laravel. Las del proyecto estan publicadas en
        // resources/views/vendor/pagination/ y las cubre el glob de arriba.
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                // Barlow: grotesca técnica, de las que se usan en señalética
                // e industria. Antes estaba Figtree, que ni siquiera se
                // cargaba (no había @font-face ni link a Google Fonts).
                sans: ['Barlow', ...defaultTheme.fontFamily.sans],
                // IBM Plex Mono: para códigos, IDs, importes y todo dato
                // que tenga que leerse como dato y no como texto.
                mono: ['IBM Plex Mono', ...defaultTheme.fontFamily.mono],
            },

            colors: {
                // Naranja de seguridad (señalización industrial).
                hazard: {
                    50: '#FFF7ED',
                    100: '#FFEDD5',
                    200: '#FED7AA',
                    300: '#FDBA74',
                    400: '#FB923C',
                    500: '#F97316',
                    600: '#EA580C',
                    700: '#C2410C',
                    800: '#9A3412',
                    900: '#7C2D12',
                    950: '#431407',
                },
            },

            borderRadius: {
                // Mas recto: la industria es precisa, no redondeada.
                // Los radios de Tailwind se achican todos; `full` se deja
                // para los avatares circulares.
                DEFAULT: '0.25rem',
                none: '0',
                sm: '0.125rem',
                md: '0.25rem',
                lg: '0.25rem',
                xl: '0.375rem',
                '2xl': '0.5rem',
                '3xl': '0.75rem',
                full: '9999px',
            },

            boxShadow: {
                // Sombras más duras, sin el difuminado suave.
                card: '0 1px 2px 0 rgb(0 0 0 / 0.06), 0 1px 3px 0 rgb(0 0 0 / 0.10)',
                panel: '0 10px 15px -3px rgb(0 0 0 / 0.10), 0 4px 6px -4px rgb(0 0 0 / 0.10)',
            },

            letterSpacing: {
                // Etiquetas técnicas: mayúsculas y bien separadas.
                label: '0.1em',
            },
        },
    },

    plugins: [forms],
};
