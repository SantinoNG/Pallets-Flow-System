<?php

namespace App\Support;

/**
 * IVA argentino.
 *
 * En Argentina los precios al publico se muestran CON IVA incluido. El
 * comprobante, en cambio, tiene que mostrar el desglose: neto + IVA = total.
 * Si no se desglosa, el comprobante no sirve como documento de venta.
 *
 * La alicuota general es del 21%. Hay una reducida del 10,5% (alimentos,
 * algunos servicios) que este catalogo no usa, pero queda declarada para
 * cuando algun articulo la necesite.
 *
 * Lo que NO se hace aca (y conviene que quede claro): no se emite CAE ni se
 * habla con AFIP. El sistema arma el comprobante con la numeracion y el
 * desglose, pero la autorizacion fiscal electronica es de otro sistema.
 */
class Iva
{
    /** Alicuota general. */
    public const TASA_GENERAL = 21.0;

    /** Alicuota reducida (no se usa todavia en el catalogo). */
    public const TASA_REDUCIDA = 10.5;

    /**
     * Desglosa un importe que YA tiene el IVA incluido.
     *
     * Se redondea el neto y el IVA se saca por diferencia: asi
     * neto + iva da exactamente el total, sin centavos perdidos.
     *
     * @return array{tasa: float, neto: float, iva: float, total: float}
     */
    public static function desglosar(float $total, float $tasa = self::TASA_GENERAL): array
    {
        $total = round($total, 2);
        $neto  = round($total / (1 + $tasa / 100), 2);

        return [
            'tasa'  => $tasa,
            'neto'  => $neto,
            'iva'   => round($total - $neto, 2),
            'total' => $total,
        ];
    }

    /**
     * Texto de la alicuota para mostrar: "21%" o "10,5%".
     */
    public static function etiqueta(float $tasa): string
    {
        $texto = rtrim(rtrim(number_format($tasa, 2, ',', '.'), '0'), ',');

        return $texto . '%';
    }
}
