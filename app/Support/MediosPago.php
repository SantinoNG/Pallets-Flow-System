<?php

namespace App\Support;

/**
 * Medios de pago aceptados.
 *
 * El catalogo vive en un solo lugar: el formulario de cobro, la validacion
 * del servidor, los filtros del listado de ventas y el comprobante. Antes
 * estaba repetido en el <select> y en un `in:` del controlador, y agregar
 * un medio nuevo obligaba a acordarse de los dos.
 *
 * Los nombres son los que se guardan en la base (`sale_payments.payment_method`).
 * Si se cambia uno, hay que migrar los datos.
 */
class MediosPago
{
    public const EFECTIVO          = 'Efectivo';
    public const TARJETA_DEBITO    = 'Tarjeta de débito';
    public const TARJETA_CREDITO   = 'Tarjeta de crédito';
    public const TRANSFERENCIA     = 'Transferencia';
    public const MERCADO_PAGO      = 'Mercado Pago';

    /**
     * Cuotas que se ofrecen con tarjeta de credito.
     *
     * No se guarda interes: el importe del pago es lo que se cobra y las
     * cuotas son la forma de pago ("6 cuotas de $X"). Si algun dia se
     * agrega interes, va como un recargo en el importe.
     */
    public const CUOTAS = [1, 3, 6, 9, 12, 18, 24];

    /**
     * Todos los medios, en el orden en que se muestran.
     *
     * @return list<string>
     */
    public static function todos(): array
    {
        return [
            self::EFECTIVO,
            self::TARJETA_DEBITO,
            self::TARJETA_CREDITO,
            self::TRANSFERENCIA,
            self::MERCADO_PAGO,
        ];
    }

    /**
     * ¿Es un medio valido?
     */
    public static function existe(string $medio): bool
    {
        return in_array($medio, self::todos(), true);
    }

    /**
     * Resumen de los medios usados en una venta.
     *
     * - Un solo medio: se guarda tal cual ("Efectivo").
     * - Varios: "Pago combinado (Efectivo + Transferencia)".
     *
     * El resumen se guarda en `sales.payment_method` para que el listado y
     * los filtros viejos sigan funcionando. El detalle con importes vive en
     * `sale_payments`.
     *
     * @param  array<int, string>  $medios
     */
    public static function resumen(array $medios): string
    {
        // Sin duplicados y en el orden del catalogo, no en el que llegaron.
        $unicos = array_values(array_intersect(self::todos(), array_unique($medios)));

        // Un medio que no este en el catalogo (dato viejo) igual se muestra.
        foreach (array_unique($medios) as $medio) {
            if (! in_array($medio, $unicos, true)) {
                $unicos[] = $medio;
            }
        }

        if (count($unicos) === 1) {
            return $unicos[0];
        }

        return 'Pago combinado (' . implode(' + ', $unicos) . ')';
    }

    /**
     * ¿El resumen corresponde a un pago combinado?
     */
    public static function esCombinado(string $resumen): bool
    {
        return str_starts_with($resumen, 'Pago combinado');
    }

    /**
     * ¿Este medio se puede pagar en cuotas?
     *
     * Solo la tarjeta de credito. El debito, la transferencia y el efectivo
     * se cobran en un pago.
     */
    public static function permiteCuotas(string $medio): bool
    {
        return $medio === self::TARJETA_CREDITO;
    }

    /**
     * Cuotas normalizadas para un medio.
     *
     * La tarjeta de credito usa las cuotas que vienen (si son validas);
     * cualquier otro medio queda en 1, aunque el formulario mande otra cosa.
     */
    public static function cuotas(string $medio, mixed $cuotas): int
    {
        if (! self::permiteCuotas($medio)) {
            return 1;
        }

        $cuotas = (int) $cuotas;

        return in_array($cuotas, self::CUOTAS, true) ? $cuotas : 1;
    }
}
