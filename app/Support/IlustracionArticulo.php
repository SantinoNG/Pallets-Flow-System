<?php

namespace App\Support;

/**
 * Ilustraciones de los artículos y logo de la marca, en SVG.
 *
 * La marca PALLETS FLOW (diseño natural) usa: pergamino crema con trama
 * diagonal, la foto del mueble dentro de un vineta circular, madera en tonos
 * calidos y plantas. Como no siempre hay fotos, estas ilustraciones
 * rehacen ese lenguaje en SVG: se ven nitidas a cualquier tamaño y pesan un
 * par de KB.
 *
 * Cuando lleguen las fotos reales, se suben desde Articulos > Editar y
 * reemplazan a estas (el seeder no toca los artículos que ya tienen
 * imagen).
 */
class IlustracionArticulo
{
    // Paleta de la marca.
    public const PAPEL        = '#F3E7CE';   // pergamino de la ficha
    public const PAPEL_CLARO  = '#FAF4E6';   // interior del circulo
    public const MADERA       = '#C19A6B';   // madera clara
    public const MADERA_OSC   = '#8F6B45';   // contorno de la madera
    public const TIERRA       = '#B5714E';   // macetas
    public const HOJA         = '#7C9E5C';   // hojas
    public const TINTA        = '#4A3728';   // tinta

    /**
     * SVG de un artículo, con su nombre.
     */
    public static function ficha(string $nombreArticulo): string
    {
        $dibujar = self::muebles()[$nombreArticulo] ?? null;

        $interior = $dibujar ? $dibujar() : self::cajaGenerica();

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800" width="800" height="800" role="img" aria-label="'
            . htmlspecialchars($nombreArticulo, ENT_XML1) . '">'
            . self::fondo()
            . '<g>' . $interior . '</g>'
            . '</svg>';
    }

    // ------------------------------------------------------------ helpers

    /**
     * Tabla de madera con su contorno.
     */
    private static function tabla(float $x, float $y, float $w, float $h, string $relleno = self::MADERA, float $r = 6): string
    {
        return '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" rx="' . $r . '" fill="' . $relleno . '"/>'
            . '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" rx="' . $r . '" fill="none" stroke="' . self::MADERA_OSC . '" stroke-width="4"/>';
    }

    /**
     * Taco (bloque) de un pallet.
     */
    private static function taco(float $x, float $y, float $w, float $h): string
    {
        return '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" rx="5" fill="' . self::MADERA . '"/>'
            . '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" rx="5" fill="none" stroke="' . self::MADERA_OSC . '" stroke-width="4"/>';
    }

    /**
     * Maceta con una planta.
     */
    private static function planta(float $cx, float $yBase, float $escala = 1): string
    {
        $r = 34 * $escala;
        $h = 44 * $escala;
        $yTierra = $yBase - $h;

        $s = '';

        // Hojas: tres trazos que salen de la tierra.
        foreach ([[-26, -70, -18], [0, -84, 4], [26, -66, 16]] as $hoja) {
            [$dx, $dy] = $hoja;

            $s .= '<path d="M ' . $cx . ' ' . ($yTierra - 6) . ' Q ' . ($cx + $dx * $escala) . ' ' . ($yTierra + $dy * $escala * 0.4)
                . ' ' . ($cx + $dx * $escala * 1.5) . ' ' . ($yTierra + $dy * $escala) . '" fill="none" stroke="' . self::HOJA . '" stroke-width="' . (7 * $escala) . '" stroke-linecap="round"/>';
        }

        // Maceta.
        $s .= '<path d="M ' . ($cx - $r) . ' ' . $yTierra . ' L ' . ($cx - $r * 0.72) . ' ' . $yBase . '
            L ' . ($cx + $r * 0.72) . ' ' . $yBase . ' L ' . ($cx + $r) . ' ' . $yTierra . ' Z" fill="' . self::TIERRA . '"/>';
        $s .= '<rect x="' . ($cx - $r - 5) . '" y="' . ($yTierra - 12 * $escala) . '" width="' . ($r * 2 + 10) . '" height="' . (13 * $escala) . '" rx="' . (5 * $escala) . '" fill="' . self::TIERRA . '"/>';

        return $s;
    }

    /**
     * Fondo de la ficha: pergamino con tramado y el vineta circular.
     */
    private static function fondo(): string
    {
        $s = '<defs>'
            . '<radialGradient id="vineta" cx="50%" cy="42%" r="62%">'
            . '<stop offset="0%" stop-color="#FFFDF6"/>'
            . '<stop offset="70%" stop-color="' . self::PAPEL_CLARO . '"/>'
            . '<stop offset="100%" stop-color="#E7D6B2"/>'
            . '</radialGradient>'
            . '<pattern id="trama" width="16" height="16" patternUnits="userSpaceOnUse" patternTransform="rotate(35)">'
            . '<line x1="0" y1="0" x2="0" y2="16" stroke="#8A6A45" stroke-opacity=".07" stroke-width="7"/>'
            . '</pattern>'
            . '</defs>';

        $s .= '<rect width="800" height="800" fill="' . self::PAPEL . '"/>';
        $s .= '<rect width="800" height="800" fill="url(#trama)"/>';
        $s .= '<circle cx="400" cy="400" r="292" fill="url(#vineta)"/>';
        $s .= '<circle cx="400" cy="400" r="292" fill="none" stroke="#D9C49B" stroke-width="6"/>';

        return $s;
    }

    /**
     * Caja genérica, para los artículos que no tienen ilustración propia.
     */
    private static function cajaGenerica(): string
    {
        return self::tabla(260, 380, 280, 200)
            . self::tabla(260, 380, 280, 40, self::MADERA);
    }

    // ------------------------------------------------------------ muebles

    /**
     * Las ilustraciones, por nombre de artículo.
     *
     * @return array<string, callable>
     */
    public static function muebles(): array
    {
        return [
            // --- Macetero: pallet bajo con dos cajones y una planta encima.
            'Macetero de pallet' => function () {
                $s = self::tabla(268, 470, 264, 34);
                $s .= '<rect x="292" y="504" width="216" height="96" rx="8" fill="' . self::MADERA . '"/>';
                $s .= '<rect x="292" y="504" width="216" height="96" rx="8" fill="none" stroke="' . self::MADERA_OSC . '" stroke-width="4"/>';
                $s .= '<rect x="308" y="520" width="184" height="32" rx="5" fill="#E3D2B0" stroke="' . self::MADERA_OSC . '" stroke-width="3"/>';
                $s .= '<rect x="308" y="558" width="184" height="32" rx="5" fill="#E3D2B0" stroke="' . self::MADERA_OSC . '" stroke-width="3"/>';
                $s .= '<rect x="378" y="532" width="44" height="8" rx="4" fill="' . self::MADERA_OSC . '"/>';
                $s .= '<rect x="378" y="570" width="44" height="8" rx="4" fill="' . self::MADERA_OSC . '"/>';
                $s .= self::taco(300, 600, 34, 46);
                $s .= self::taco(466, 600, 34, 46);
                $s .= self::planta(400, 470, 0.95);

                return $s;
            },

            // --- Estantería: pallet vertical con tres baldas, plantas y libros.
            'Estantería de pallet' => function () {
                $s = self::taco(258, 250, 40, 330);
                $s .= self::taco(502, 250, 40, 330);
                $s .= self::tabla(258, 300, 284, 26);
                $s .= self::tabla(258, 410, 284, 26);
                $s .= self::tabla(258, 520, 284, 26);

                $s .= '<rect x="300" y="356" width="22" height="54" rx="3" fill="#A8624A"/>';
                $s .= '<rect x="326" y="364" width="20" height="46" rx="3" fill="#6B8E4E"/>';
                $s .= '<rect x="350" y="352" width="24" height="58" rx="3" fill="#8A6A45"/>';
                $s .= '<rect x="378" y="368" width="18" height="42" rx="3" fill="#C19A6B"/>';

                $s .= self::planta(330, 300, 0.62);
                $s .= self::planta(455, 410, 0.66);
                $s .= self::planta(360, 520, 0.58);

                return $s;
            },

            // --- Sillón individual: base de pallet, cojín grande y una manta.
            'Sillón individual de pallet' => function () {
                $s = self::tabla(246, 546, 308, 32);
                $s .= self::taco(258, 578, 40, 46);
                $s .= self::taco(502, 578, 40, 46);

                $s .= '<rect x="268" y="452" width="264" height="96" rx="30" fill="#F7F1E3" stroke="#D9C8A6" stroke-width="4"/>';
                $s .= '<rect x="288" y="252" width="224" height="200" rx="34" fill="#F7F1E3" stroke="#D9C8A6" stroke-width="4"/>';
                $s .= '<rect x="318" y="286" width="164" height="104" rx="28" fill="#EFE3CB" stroke="#D9C8A6" stroke-width="4"/>';
                $s .= '<path d="M 470 300 Q 536 320 530 400 L 524 470 Q 500 482 486 462 L 478 330 Z" fill="#C9B48A" opacity=".9"/>';

                return $s;
            },

            // --- Sillón doble: más ancho, dos cojines.
            'Sillón doble de pallet' => function () {
                $s = self::tabla(196, 546, 408, 32);
                $s .= self::taco(210, 578, 42, 46);
                $s .= self::taco(548, 578, 42, 46);

                $s .= '<rect x="220" y="452" width="360" height="96" rx="30" fill="#F7F1E3" stroke="#D9C8A6" stroke-width="4"/>';
                $s .= '<rect x="244" y="252" width="312" height="200" rx="34" fill="#F7F1E3" stroke="#D9C8A6" stroke-width="4"/>';
                $s .= '<rect x="272" y="286" width="126" height="104" rx="28" fill="#EFE3CB" stroke="#D9C8A6" stroke-width="4"/>';
                $s .= '<rect x="410" y="286" width="126" height="104" rx="28" fill="#EFE3CB" stroke="#D9C8A6" stroke-width="4"/>';
                $s .= '<path d="M 520 300 Q 586 320 580 400 L 574 470 Q 550 482 536 462 L 528 330 Z" fill="#C9B48A" opacity=".9"/>';

                return $s;
            },

            // --- Mesa ratona: pallet bajo con ruedas.
            'Mesa ratona de pallet' => function () {
                $s = self::tabla(206, 420, 388, 30);
                $s .= self::tabla(228, 450, 344, 22);
                $s .= self::taco(252, 472, 34, 40);
                $s .= self::taco(514, 472, 34, 40);

                foreach ([[252, 522], [514, 522], [252, 452], [514, 452]] as $r) {
                    $s .= '<circle cx="' . $r[0] . '" cy="' . $r[1] . '" r="17" fill="#6F6A63"/>';
                    $s .= '<circle cx="' . $r[0] . '" cy="' . $r[1] . '" r="7" fill="#4A4741"/>';
                }

                $s .= '<rect x="330" y="378" width="96" height="34" rx="5" fill="#EFE3CB" stroke="' . self::MADERA_OSC . '" stroke-width="3"/>';
                $s .= '<path d="M 360 366 L 366 344 Q 400 336 432 348 L 440 366 Z" fill="#F7F1E3" stroke="' . self::MADERA_OSC . '" stroke-width="3"/>';

                return $s;
            },

            // --- Mesa de comedor: más alta y ancha.
            'Mesa de comedor de pallet' => function () {
                $s = self::tabla(178, 388, 444, 34);
                $s .= self::taco(228, 422, 44, 118);
                $s .= self::taco(528, 422, 44, 118);
                $s .= '<rect x="272" y="422" width="256" height="18" rx="5" fill="' . self::MADERA . '" stroke="' . self::MADERA_OSC . '" stroke-width="4"/>';

                $s .= '<path d="M 372 388 L 368 330 Q 400 320 432 332 L 428 388 Z" fill="' . self::TIERRA . '"/>';
                $s .= '<path d="M 400 330 Q 372 290 356 262" fill="none" stroke="' . self::HOJA . '" stroke-width="7" stroke-linecap="round"/>';
                $s .= '<path d="M 400 326 Q 424 286 448 266" fill="none" stroke="' . self::HOJA . '" stroke-width="7" stroke-linecap="round"/>';

                return $s;
            },

            // --- Mueble de TV: cajón bajo y televisor.
            'Mueble TV de pallet' => function () {
                $s = self::tabla(196, 470, 408, 30);
                $s .= '<rect x="222" y="500" width="356" height="106" rx="8" fill="' . self::MADERA . '"/>';
                $s .= '<rect x="222" y="500" width="356" height="106" rx="8" fill="none" stroke="' . self::MADERA_OSC . '" stroke-width="4"/>';
                $s .= '<rect x="246" y="518" width="146" height="70" rx="6" fill="#E3D2B0" stroke="' . self::MADERA_OSC . '" stroke-width="3"/>';
                $s .= '<rect x="408" y="518" width="146" height="70" rx="6" fill="#E3D2B0" stroke="' . self::MADERA_OSC . '" stroke-width="3"/>';
                $s .= self::taco(240, 606, 34, 40);
                $s .= self::taco(526, 606, 34, 40);

                $s .= '<rect x="268" y="252" width="264" height="164" rx="10" fill="#3B3B3F"/>';
                $s .= '<rect x="280" y="264" width="240" height="140" rx="6" fill="#5C6B7A"/>';
                $s .= '<rect x="386" y="416" width="28" height="26" fill="#3B3B3F"/>';
                $s .= '<rect x="344" y="442" width="112" height="16" rx="8" fill="#3B3B3F"/>';

                $s .= '<path d="M 570 470 L 566 414 Q 594 404 622 416 L 618 470 Z" fill="' . self::TIERRA . '"/>';
                $s .= '<path d="M 594 414 Q 574 384 566 362" fill="none" stroke="' . self::HOJA . '" stroke-width="7" stroke-linecap="round"/>';

                return $s;
            },

            // --- Cama de 2 plazas: cabecera, colchón y almohadas.
            'Cama de pallet 2 plazas' => function () {
                $s = '<rect x="196" y="236" width="408" height="112" rx="14" fill="' . self::MADERA . '" stroke="' . self::MADERA_OSC . '" stroke-width="4"/>';
                $s .= '<rect x="212" y="252" width="376" height="80" rx="10" fill="#E3D2B0"/>';
                $s .= '<rect x="212" y="356" width="376" height="96" rx="16" fill="#F7F1E3" stroke="#D9C8A6" stroke-width="4"/>';
                $s .= '<rect x="236" y="322" width="140" height="52" rx="16" fill="#EFE3CB" stroke="#D9C8A6" stroke-width="4"/>';
                $s .= '<rect x="424" y="322" width="140" height="52" rx="16" fill="#EFE3CB" stroke="#D9C8A6" stroke-width="4"/>';
                $s .= self::tabla(212, 452, 376, 30);
                $s .= self::taco(228, 482, 38, 44);
                $s .= self::taco(534, 482, 38, 44);

                return $s;
            },
        ];
    }
}
